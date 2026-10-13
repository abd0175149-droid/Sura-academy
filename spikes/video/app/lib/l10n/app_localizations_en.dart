// ignore: unused_import
import 'package:intl/intl.dart' as intl;

import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for English (`en`).
class AppLocalizationsEn extends AppLocalizations {
  AppLocalizationsEn([String locale = 'en']) : super(locale);

  @override
  String get appTitle => 'Video spike';

  @override
  String get joinTitle => 'Join a test room';

  @override
  String get provider => 'Provider';

  @override
  String get zoomWebUnavailable =>
      'Zoom does not run inside the Flutter app in a browser. Open the Zoom web page from the same link.';

  @override
  String get role => 'Role';

  @override
  String get roleTeacher => 'Teacher';

  @override
  String get roleStudent => 'Student';

  @override
  String get roleSubstitute => 'Substitute';

  @override
  String get roleSupervisor => 'Supervisor';

  @override
  String get roleBot => 'Bot';

  @override
  String get seat => 'Seat number';

  @override
  String get room => 'Room name';

  @override
  String get device => 'Device name';

  @override
  String get deviceHint => 'e.g. ipad-safari or android-1';

  @override
  String get network => 'Network';

  @override
  String get networkNormal => 'Normal';

  @override
  String get networkLimited => 'Limited 1.5 Mbit/s';

  @override
  String get accessCode => 'Spike access code';

  @override
  String get serverUrl => 'Server address';

  @override
  String get join => 'Join';

  @override
  String autoTrials(int count) {
    return '$count automatic trials';
  }

  @override
  String get leave => 'Leave';

  @override
  String get microphone => 'Microphone';

  @override
  String get camera => 'Camera';

  @override
  String get muteAll => 'Mute all';

  @override
  String get remove => 'Remove';

  @override
  String get lockRoom => 'Lock room';

  @override
  String get unlockRoom => 'Unlock room';

  @override
  String get recordStart => 'Start recording';

  @override
  String get recordStop => 'Stop recording';

  @override
  String quality(String quality) {
    return 'Connection: $quality';
  }

  @override
  String get qualityExcellent => 'excellent';

  @override
  String get qualityGood => 'good';

  @override
  String get qualityPoor => 'poor';

  @override
  String get qualityLost => 'lost';

  @override
  String get qualityUnknown => 'unknown';

  @override
  String get audioOnly => 'Audio-only mode';

  @override
  String joinTime(String seconds) {
    return 'Join to first video: $seconds s';
  }

  @override
  String get waitingVideo => 'Waiting for video…';

  @override
  String trialProgress(int n, int total) {
    return 'Trial $n of $total';
  }

  @override
  String trialsDone(int ok, int total, String p50, String p95) {
    return 'Trials done ($ok of $total succeeded). Median $p50 s, 95% under $p95 s';
  }

  @override
  String error(String message) {
    return 'Error: $message';
  }

  @override
  String get done => 'Done';

  @override
  String get you => 'You';

  @override
  String trialTimes(String token, String connected) {
    return 'token $token ms · connected $connected ms';
  }

  @override
  String trialSeconds(int n, String seconds) {
    return '$n. $seconds s';
  }
}
