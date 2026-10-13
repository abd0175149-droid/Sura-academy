/// Browser access for the spike. Real implementation on the web, inert stub elsewhere (Android
/// builds only need the reading screen for the screenshot comparison).
library;

export 'browser_stub.dart' if (dart.library.js_interop) 'browser_web.dart';
