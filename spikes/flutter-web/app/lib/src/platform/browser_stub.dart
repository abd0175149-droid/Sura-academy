import 'dart:async';

const bool isBrowser = false;

/// `<meta name="sura-…">` values written by the spike server into index.html.
String pageMeta(String name) => '';

String queryParam(String name) => '';

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
