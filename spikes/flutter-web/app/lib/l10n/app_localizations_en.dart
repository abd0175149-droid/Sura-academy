// ignore: unused_import
import 'package:intl/intl.dart' as intl;

import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for English (`en`).
class AppLocalizationsEn extends AppLocalizations {
  AppLocalizationsEn([String locale = 'en']) : super(locale);

  @override
  String get appTitle => 'Flutter web spike';

  @override
  String get tabReading => 'Vocalized text';

  @override
  String get tabForm => 'Form';

  @override
  String get tabPayment => 'Payment';

  @override
  String get tabVideo => 'Video';

  @override
  String get tabMeasure => 'Measurements';

  @override
  String get tabFeedback => 'Your notes';

  @override
  String get webOnly => 'This page works in the browser only.';

  @override
  String get showDiacritics => 'Show diacritics';

  @override
  String get textSize => 'Text size';

  @override
  String get textScaleNormal => 'Normal';

  @override
  String get textScaleLarger => 'Larger';

  @override
  String get textScaleLargest => 'Largest';

  @override
  String fontSampleTitle(String font, int size) {
    return '$font at $size';
  }

  @override
  String get capture => 'Capture image';

  @override
  String get captureSent => 'Image received.';

  @override
  String captureFailed(String reason) {
    return 'Could not send the image: $reason';
  }

  @override
  String get formTitle => 'Test form';

  @override
  String get nameLabel => 'Test name';

  @override
  String get nameHint => 'Type any made-up name, not a real one';

  @override
  String get notesLabel => 'Notes';

  @override
  String get listTitle => 'Lesson list';

  @override
  String lessonItem(int number) {
    return 'Lesson $number';
  }

  @override
  String get counterButton => 'Tap here';

  @override
  String counterValue(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: 'Tapped $count times',
      one: 'Tapped once',
      zero: 'Not tapped yet',
    );
    return '$_temp0';
  }

  @override
  String get paymentIntro =>
      'A fake payment page on another site. No money moves.';

  @override
  String get payRedirect => 'Full-page redirect to the payment page';

  @override
  String get payPopup => 'Payment page in a popup';

  @override
  String get payFrame => 'Payment page inside a frame';

  @override
  String get payFrameCorp => 'Inside a frame that allows embedding';

  @override
  String payResult(String status) {
    return 'Payment result: $status';
  }

  @override
  String get payStatusPaid => 'Paid';

  @override
  String get payStatusCancelled => 'Cancelled';

  @override
  String get payStatusNone => 'No result yet';

  @override
  String get payPopupNoOpener => 'The popup cannot talk to the app';

  @override
  String get payClose => 'Close the frame';

  @override
  String get videoTargetLocal => 'Local server';

  @override
  String get videoTargetCloud => 'LiveKit Cloud';

  @override
  String get videoJoin => 'Join room';

  @override
  String get videoLeave => 'Leave';

  @override
  String get videoConnected => 'Connected';

  @override
  String get videoConnecting => 'Connecting';

  @override
  String get videoDisconnected => 'Not connected';

  @override
  String videoFailed(String reason) {
    return 'Connection failed: $reason';
  }

  @override
  String videoRemoteCount(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: '$count other participants',
      one: 'One other participant',
      zero: 'Nobody else in the room',
    );
    return '$_temp0';
  }

  @override
  String get videoYou => 'You';

  @override
  String get measureRuntime => 'Runtime';

  @override
  String get measureWasm => 'Fast build (WebAssembly)';

  @override
  String get measureJs => 'Regular build (JavaScript)';

  @override
  String get measureThreads => 'Render threads';

  @override
  String get measureMulti => 'Multiple';

  @override
  String get measureSingle => 'Single';

  @override
  String get measureIsolation => 'Isolation headers';

  @override
  String get measureOn => 'On';

  @override
  String get measureOff => 'Off';

  @override
  String get measureServiceWorker => 'Service worker';

  @override
  String get measureSwControlled => 'Serving this page';

  @override
  String get measureSwNone => 'Not serving this page yet';

  @override
  String get measureTimings => 'This load\'s timings (ms)';

  @override
  String get markSplash => 'Splash shown';

  @override
  String get markEntrypoint => 'Code loaded';

  @override
  String get markEngine => 'Engine ready';

  @override
  String get markFirstFrame => 'First frame';

  @override
  String get markInteractive => 'Interactive';

  @override
  String get measureBytes => 'Downloaded (KB)';

  @override
  String get measureVariant => 'Current variant';

  @override
  String get autoRun => 'Run measurements automatically';

  @override
  String get clearCaches => 'Clear caches';

  @override
  String get cachesCleared => 'Caches cleared. Reload the page.';

  @override
  String get notAvailable => 'Not available';

  @override
  String get feedbackIntro =>
      'After testing, choose yes or no for each question, then tap Send.';

  @override
  String get feedbackQ1 =>
      'Did VoiceOver read the Arabic text with an Arabic voice?';

  @override
  String get feedbackQ2 => 'Did it read field and button names in Arabic?';

  @override
  String get feedbackQ3 => 'Did it read the list items one by one?';

  @override
  String get feedbackQ4 => 'Did typing with the Arabic keyboard work?';

  @override
  String get feedbackQ5 =>
      'Did the cursor move correctly while typing and editing?';

  @override
  String get feedbackQ6 =>
      'Were the diacritics shown correctly, without overlap or clipping?';

  @override
  String get feedbackQ7 =>
      'Did the app install to the home screen and open without the browser bar?';

  @override
  String get feedbackQ8 =>
      'Did you return from the payment page to the installed app with the result shown?';

  @override
  String get feedbackQ9 => 'Did the video show with the isolation headers on?';

  @override
  String get yes => 'Yes';

  @override
  String get no => 'No';

  @override
  String get send => 'Send';

  @override
  String get feedbackSent => 'Your answers arrived, thank you.';

  @override
  String feedbackFailed(String reason) {
    return 'Could not send: $reason';
  }
}
