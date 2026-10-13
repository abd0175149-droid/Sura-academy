import 'dart:typed_data';
import 'dart:ui' as ui;

import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart';

import '../../l10n/app_localizations.dart';
import '../arabic/diacritics.dart';
import '../theme.dart';
import 'samples.dart';

/// Called with a PNG of the samples area and a short description of what it shows.
typedef CaptureSink = Future<void> Function(
  Uint8List png,
  Map<String, String> meta,
);

class ReadingPage extends StatefulWidget {
  const ReadingPage({super.key, this.onCapture});

  final CaptureSink? onCapture;

  @override
  State<ReadingPage> createState() => _ReadingPageState();
}

class _ReadingPageState extends State<ReadingPage> {
  static const _sizes = [16, 24, 32];
  static const _families = [SpikeFonts.content, SpikeFonts.ui];

  final _captureKey = GlobalKey();
  bool _showDiacritics = true;
  double _scale = 1.0;
  String? _captureStatus;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final outer = MediaQuery.textScalerOf(context);
    return ListView(
      padding: const EdgeInsetsDirectional.all(16),
      children: [
        Wrap(
          spacing: 24,
          runSpacing: 12,
          crossAxisAlignment: WrapCrossAlignment.center,
          children: [
            // One labelled switch for screen readers, not a switch plus a separate text.
            MergeSemantics(
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Switch(
                    key: const ValueKey('diacritics-switch'),
                    value: _showDiacritics,
                    onChanged: (v) => setState(() => _showDiacritics = v),
                  ),
                  const SizedBox(width: 8),
                  Text(l10n.showDiacritics),
                ],
              ),
            ),
            Semantics(
              container: true,
              label: l10n.textSize,
              child: SegmentedButton<double>(
                segments: [
                  ButtonSegment(value: 1.0, label: Text(l10n.textScaleNormal)),
                  ButtonSegment(value: 1.3, label: Text(l10n.textScaleLarger)),
                  ButtonSegment(value: 2.0, label: Text(l10n.textScaleLargest)),
                ],
                selected: {_scale},
                onSelectionChanged: (s) => setState(() => _scale = s.first),
              ),
            ),
            if (widget.onCapture != null)
              FilledButton.tonal(
                onPressed: _capture,
                child: Text(l10n.capture),
              ),
          ],
        ),
        if (_captureStatus != null)
          Padding(
            padding: const EdgeInsetsDirectional.only(top: 8),
            child: Text(_captureStatus!),
          ),
        const SizedBox(height: 16),
        RepaintBoundary(
          key: _captureKey,
          child: ColoredBox(
            color: Theme.of(context).colorScheme.surface,
            child: MediaQuery(
              data: MediaQuery.of(context).copyWith(
                textScaler: TextScaler.linear(outer.scale(1) * _scale),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  for (final family in _families)
                    for (final size in _sizes) _section(context, family, size),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _section(BuildContext context, String family, int size) {
    final l10n = AppLocalizations.of(context);
    final style = TextStyle(
      fontFamily: family,
      fontFamilyFallback: const [SpikeFonts.ui],
      fontSize: size.toDouble(),
      // Vocalized text needs room above and below for marks (≥ 1.9, arabic-typography skill).
      height: 1.9,
      color: Theme.of(context).colorScheme.onSurface,
    );
    return Padding(
      padding: const EdgeInsetsDirectional.only(bottom: 24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Semantics(
            header: true,
            headingLevel: 2,
            child: Text(
              l10n.fontSampleTitle(family, size),
              style: Theme.of(context).textTheme.titleSmall
                  ?.copyWith(color: Theme.of(context).colorScheme.primary),
            ),
          ),
          for (final (i, sample) in ReadingSamples.all.indexed)
            Text(
              _showDiacritics ? sample : hideDiacritics(sample),
              key: ValueKey('sample:$family:$size:$i'),
              style: style,
              textAlign: TextAlign.start,
            ),
        ],
      ),
    );
  }

  Future<void> _capture() async {
    final l10n = AppLocalizations.of(context);
    final boundary =
        _captureKey.currentContext!.findRenderObject()!
            as RenderRepaintBoundary;
    final ratio = MediaQuery.devicePixelRatioOf(context);
    try {
      final image = await boundary.toImage(pixelRatio: ratio);
      final bytes = await image.toByteData(format: ui.ImageByteFormat.png);
      image.dispose();
      await widget.onCapture!(bytes!.buffer.asUint8List(), {
        'diacritics': _showDiacritics ? 'on' : 'off',
        'scale': _scale.toString(),
      });
      if (mounted) setState(() => _captureStatus = l10n.captureSent);
    } on Object catch (e) {
      if (mounted) setState(() => _captureStatus = l10n.captureFailed('$e'));
    }
  }
}
