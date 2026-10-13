import 'dart:async';

const bool isBrowser = false;

/// `<meta name="sura-…">` values written by the spike server into index.html.
String pageMeta(String name) => name == 'variant' ? 'native' : '';

/// Off the web there is no URL: the device label is fixed so screenshots and answers validate.
String queryParam(String name) => name == 'd' ? 'android-app' : '';

/// Run key for the spike server's POST endpoints.
String runKey() => const String.fromEnvironment('SPIKE_KEY');

void mark(String name) {}

/// Startup marks in ms since navigation start.
Map<String, double> startupMarks() => const {};

/// Runtime facts: isolation, service worker, engine, bytes downloaded, …
Map<String, Object?> environment() => const {};

/// Exposes a probe result to automation (`window.suraProbe[key]`).
void reportProbe(String key, Map<String, Object?> value) {}

/// Hands control back to the auto-run page once the service worker is ready.
void autoNext() {}

void navigate(String url) {}

bool openPopup(String url) => false;

/// JSON messages posted to this window from [origin].
Stream<Map<String, Object?>> messagesFrom(String origin) =>
    const Stream.empty();

void configureIframe(Object element, String src) {}

Future<void> clearCaches() async {}

/// An Arabic voice of the device's speech engine (Web Speech API on the web).
typedef SpeechVoice = ({String name, String lang, bool onDevice});

List<SpeechVoice> arabicVoices() => const [];

/// Speaks [text] with the named voice; false when the browser has no speech engine.
bool speak(String text, {required String voiceName, double rate = 0.9}) =>
    false;

void stopSpeaking() {}

/// Called once when the browser's voice list arrives (it loads asynchronously in Chrome).
void onVoicesChanged(void Function() callback) {}
