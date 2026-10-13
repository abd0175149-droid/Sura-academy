import 'dart:async';

import 'package:flutter/semantics.dart';
import 'package:flutter/widgets.dart';

import 'src/api.dart';
import 'src/app.dart';
import 'src/measure/startup.dart';
import 'src/platform/browser.dart' as browser;

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  // ADR-010 §4: the semantics tree is on from the start so screen readers work without the
  // "enable accessibility" button. `?sem=0` turns it off to measure its start-up cost.
  final semantics = browser.queryParam('sem') != '0';
  if (semantics) SemanticsBinding.instance.ensureSemantics();
  runApp(const SpikeApp());
  final binding = WidgetsBinding.instance;
  binding.addPostFrameCallback((_) {
    binding.addPostFrameCallback(
      (_) => unawaited(onInteractive(SpikeApi(), semantics: semantics)),
    );
    binding.scheduleFrame();
  });
}
