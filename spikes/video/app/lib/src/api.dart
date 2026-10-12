import 'dart:convert';

import 'package:http/http.dart' as http;

/// What the spike server returns from `POST /token`.
class TokenGrant {
  TokenGrant.fromJson(Map<String, dynamic> json)
    : provider = json['provider'] as String,
      token = json['token'] as String,
      serverUrl = json['server_url'] as String?,
      sessionName = json['session_name'] as String?,
      room = json['room'] as String,
      identity = json['identity'] as String,
      role = json['role'] as String,
      displayName = json['display_name'] as String,
      adminTicket = json['admin_ticket'] as String?;

  final String provider;
  final String token;
  final String? serverUrl;
  final String? sessionName;
  final String room;
  final String identity;
  final String role;
  final String displayName;

  /// Only teacher and substitute get one; it authorizes `/admin/*` on the spike server.
  final String? adminTicket;
}

/// An error in the product's shape: `{code, message_ar, message_en, details, request_id}`.
class SpikeApiException implements Exception {
  SpikeApiException(this.status, this.code, this.messageAr, this.messageEn);

  final int status;
  final String code;
  final String messageAr;
  final String messageEn;

  @override
  String toString() => '$code ($status): $messageEn';
}

/// The spike server: tokens, metrics and host-only moderation.
class SpikeApi {
  SpikeApi(this.baseUrl, this.accessCode, {http.Client? client})
    : _http = client ?? http.Client();

  final Uri baseUrl;
  final String accessCode;
  final http.Client _http;

  Future<TokenGrant> token({
    required String provider,
    required String role,
    required String identity,
    required String room,
  }) async {
    final json = await _post('/token', {
      'provider': provider,
      'role': role,
      'identity': identity,
      'room': room,
      'access_code': accessCode,
    });
    return TokenGrant.fromJson(json);
  }

  /// One join-time trial; the server appends it to `var/metrics.jsonl`.
  Future<void> metrics(Map<String, Object?> trial) =>
      _post('/metrics', trial, headers: {'x-spike-code': accessCode});

  /// `/admin/{action}` with the host's admin ticket (mute-all, remove, lock, unlock, record/start, record/stop).
  Future<Map<String, dynamic>> admin(
    TokenGrant grant,
    String action, [
    Map<String, Object?> body = const {},
  ]) => _post(
    '/admin/$action',
    {...body, 'room': grant.room},
    headers: {'x-admin-ticket': grant.adminTicket ?? ''},
  );

  Future<Map<String, dynamic>> _post(
    String path,
    Map<String, Object?> body, {
    Map<String, String> headers = const {},
  }) async {
    final response = await _http.post(
      baseUrl.resolve(path),
      headers: {'content-type': 'application/json', ...headers},
      body: jsonEncode(body),
    );
    final decoded = response.body.isEmpty
        ? <String, dynamic>{}
        : jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode >= 300) {
      throw SpikeApiException(
        response.statusCode,
        decoded['code'] as String? ?? 'http_${response.statusCode}',
        decoded['message_ar'] as String? ?? '',
        decoded['message_en'] as String? ?? '',
      );
    }
    return decoded;
  }
}
