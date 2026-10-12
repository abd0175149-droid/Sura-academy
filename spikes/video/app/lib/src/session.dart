import 'package:flutter/foundation.dart';

import 'api.dart';
import 'livekit_video_client.dart';
import 'video_client.dart';
import 'zoom_factory.dart';

/// What the join form collects. Identities are pseudonyms (`student-2`): no real names anywhere.
class JoinRequest {
  const JoinRequest({
    required this.api,
    required this.provider,
    required this.role,
    required this.seat,
    required this.room,
    required this.device,
    required this.network,
  });

  final SpikeApi api;
  final String provider;
  final String role;
  final int seat;
  final String room;
  final String device;
  final String network;

  String get identity => '$role-$seat';

  Future<TokenGrant> fetchToken() =>
      api.token(provider: provider, role: role, identity: identity, room: room);

  SpikeVideoClient createClient() => switch (provider) {
    'zoom' =>
      createZoomClient() ??
          (throw UnsupportedError('Zoom is not available on this platform')),
    _ => LiveKitVideoClient(),
  };

  /// `web`, `android` or `iOS`; the device label tells browsers apart.
  static String get platform => kIsWeb ? 'web' : defaultTargetPlatform.name;

  Map<String, Object?> metricsBase() => {
    'provider': provider,
    'platform': platform,
    'device': device,
    'network': network,
    'role': role,
    'room': room,
  };
}
