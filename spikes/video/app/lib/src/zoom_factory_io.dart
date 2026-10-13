import 'video_client.dart';
import 'zoom_video_client.dart';

/// Android and iOS: `flutter_zoom_videosdk`.
SpikeVideoClient? createZoomClient() => ZoomVideoClient();

const bool zoomAvailable = true;
