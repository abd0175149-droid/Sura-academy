import 'dart:convert';
import 'dart:typed_data';

import 'package:http/http.dart' as http;

import 'platform/browser.dart' as browser;

/// The spike server (spikes/flutter-web/server). On the web it is the page's own origin; the
/// Android build (emulator screenshots) reaches it at the host loopback.
class SpikeApi {
  SpikeApi({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;

  static Uri get _base => browser.isBrowser
      ? Uri.base.replace(path: '/', query: '', fragment: '')
      : Uri.parse(
          const String.fromEnvironment(
            'SPIKE_SERVER',
            defaultValue: 'http://10.0.2.2:27200/',
          ),
        );

  Map<String, String> get _headers => {'X-Spike-Key': browser.runKey()};

  Future<void> postMetrics(Map<String, Object?> payload) => _check(
    _client.post(
      _base.resolve('metrics'),
      headers: {..._headers, 'Content-Type': 'application/json'},
      body: jsonEncode(payload),
    ),
  );

  Future<void> postShot(Uint8List png, Map<String, String> meta) => _check(
    _client.post(
      _base.resolve('shots').replace(queryParameters: meta),
      headers: {..._headers, 'Content-Type': 'image/png'},
      body: png,
    ),
  );

  Future<void> postFeedback(Map<String, Object?> payload) => _check(
    _client.post(
      _base.resolve('feedback'),
      headers: {..._headers, 'Content-Type': 'application/json'},
      body: jsonEncode(payload),
    ),
  );

  /// `{token, url}` for the local LiveKit dev server or LiveKit Cloud.
  Future<({String token, String url})> liveKitToken({
    required String target,
    required String room,
    required String identity,
  }) async {
    final res = await _client.post(
      _base.resolve('livekit-token'),
      headers: {..._headers, 'Content-Type': 'application/json'},
      body: jsonEncode({'target': target, 'room': room, 'identity': identity}),
    );
    if (res.statusCode != 200) {
      throw SpikeApiException(res.statusCode, res.body);
    }
    final body = jsonDecode(res.body) as Map<String, Object?>;
    return (token: body['token']! as String, url: body['url']! as String);
  }

  Future<void> _check(Future<http.Response> call) async {
    final res = await call;
    if (res.statusCode >= 300) {
      throw SpikeApiException(res.statusCode, res.body);
    }
  }
}

class SpikeApiException implements Exception {
  SpikeApiException(this.status, this.body);

  final int status;
  final String body;

  /// The server's bilingual error message for [languageCode], or the status code.
  String message(String languageCode) {
    try {
      final json = jsonDecode(body) as Map<String, Object?>;
      return (json[languageCode == 'ar' ? 'message_ar' : 'message_en']
              as String?) ??
          '$status';
    } on Object {
      return '$status';
    }
  }

  @override
  String toString() => 'HTTP $status';
}
