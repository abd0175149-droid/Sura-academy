import 'dart:async';
import 'dart:convert';
import 'dart:js_interop';
import 'dart:js_interop_unsafe';

import 'package:web/web.dart' as web;

const bool isBrowser = true;

String pageMeta(String name) {
  final el = web.document.querySelector('meta[name="sura-$name"]');
  return el?.getAttribute('content') ?? '';
}

String queryParam(String name) => Uri.base.queryParameters[name] ?? '';

String runKey() {
  final fromUrl = queryParam('k');
  try {
    if (fromUrl.isNotEmpty) {
      web.window.sessionStorage.setItem('sura-k', fromUrl);
      return fromUrl;
    }
    return web.window.sessionStorage.getItem('sura-k') ?? '';
  } on Object {
    return fromUrl;
  }
}

void mark(String name) => web.window.performance.mark(name);

Map<String, double> startupMarks() {
  final perf = web.window.performance;
  final out = <String, double>{};
  for (final entry in perf.getEntriesByType('mark').toDart) {
    if (entry.name.startsWith('sura-')) {
      out[entry.name.substring(5)] = entry.startTime;
    }
  }
  for (final entry in perf.getEntriesByType('paint').toDart) {
    if (entry.name == 'first-contentful-paint') out['fcp'] = entry.startTime;
  }
  final lcp = _spike?['lcp'];
  if (lcp != null && lcp.typeofEquals('number')) {
    out['lcp'] = (lcp as JSNumber).toDartDouble;
  }
  return out;
}

Map<String, Object?> environment() {
  final perf = web.window.performance;
  var transfer = 0;
  var encoded = 0;
  final files = <String>[];
  for (final e in perf.getEntriesByType('resource').toDart) {
    final r = e as web.PerformanceResourceTiming;
    transfer += r.transferSize;
    encoded += r.encodedBodySize;
    final name = Uri.parse(r.name).pathSegments.lastOrNull ?? '';
    if (name.endsWith('.wasm') ||
        name == 'main.dart.js' ||
        name == 'main.dart.mjs') {
      files.add(name);
    }
  }
  final nav = web.window.navigator;
  final controlled = nav.serviceWorker.controller != null;
  return {
    'isolated': web.window.crossOriginIsolated,
    'sw': controlled ? 'controlled' : 'none',
    'ua': nav.userAgent,
    'vendor': nav.vendor,
    'transfer_kb': (transfer / 1024).round(),
    'encoded_kb': (encoded / 1024).round(),
    'engine_files': files,
    'screen':
        '${web.window.screen.width}x${web.window.screen.height}@${web.window.devicePixelRatio}',
  };
}

JSObject? get _spike => web.window.getProperty<JSObject?>('suraSpike'.toJS);

void reportProbe(String key, Map<String, Object?> value) {
  var probe = web.window.getProperty<JSObject?>('suraProbe'.toJS);
  if (probe == null) {
    probe = JSObject();
    web.window.setProperty('suraProbe'.toJS, probe);
  }
  probe.setProperty(key.toJS, jsonEncode(value).toJS);
}

void autoNext() {
  final spike = _spike;
  if (spike != null && spike.has('next')) spike.callMethod<JSAny?>('next'.toJS);
}

void navigate(String url) => web.window.location.assign(url);

bool openPopup(String url) =>
    web.window.open(url, 'sura-pay', 'popup,width=480,height=640') != null;

Stream<Map<String, Object?>> messagesFrom(String origin) {
  late StreamController<Map<String, Object?>> controller;
  void onMessage(web.Event event) {
    final e = event as web.MessageEvent;
    if (e.origin != origin) return;
    final data = e.data;
    if (data == null || !data.typeofEquals('string')) return;
    try {
      final decoded = jsonDecode((data as JSString).toDart);
      if (decoded is Map<String, Object?>) controller.add(decoded);
    } on FormatException {
      // Not ours.
    }
  }

  final listener = onMessage.toJS;
  controller = StreamController(
    onListen: () => web.window.addEventListener('message', listener),
    onCancel: () => web.window.removeEventListener('message', listener),
  );
  return controller.stream;
}

void configureIframe(Object element, String src) {
  final frame = element as web.HTMLIFrameElement;
  frame
    ..src = src
    ..style.border = '0'
    ..style.width = '100%'
    ..style.height = '100%';
}

Future<void> clearCaches() async {
  final regs = await web.window.navigator.serviceWorker
      .getRegistrations()
      .toDart;
  for (final r in regs.toDart) {
    await r.unregister().toDart;
  }
  final keys = await web.window.caches.keys().toDart;
  for (final k in keys.toDart) {
    await web.window.caches.delete(k.toDart).toDart;
  }
}

typedef SpeechVoice = ({String name, String lang, bool onDevice});

List<SpeechVoice> arabicVoices() {
  final voices = <SpeechVoice>[];
  for (final v in web.window.speechSynthesis.getVoices().toDart) {
    if (v.lang.toLowerCase().startsWith('ar')) {
      voices.add((name: v.name, lang: v.lang, onDevice: v.localService));
    }
  }
  // On-device voices first: network voices send the text to the browser vendor (child privacy).
  voices.sort((a, b) => (a.onDevice == b.onDevice) ? 0 : (a.onDevice ? -1 : 1));
  return voices;
}

bool speak(String text, {required String voiceName, double rate = 0.9}) {
  final synth = web.window.speechSynthesis;
  web.SpeechSynthesisVoice? voice;
  for (final v in synth.getVoices().toDart) {
    if (v.name == voiceName) voice = v;
  }
  if (voice == null) return false;
  synth.cancel();
  final utterance = web.SpeechSynthesisUtterance(text)
    ..voice = voice
    ..lang = voice.lang
    ..rate = rate;
  synth.speak(utterance);
  return true;
}

void stopSpeaking() => web.window.speechSynthesis.cancel();

void onVoicesChanged(void Function() callback) {
  web.window.speechSynthesis.addEventListener(
    'voiceschanged',
    ((web.Event _) => callback()).toJS,
  );
}

void setDocumentLang(String languageCode) {
  web.document.documentElement?.setAttribute('lang', languageCode);
}
