// ignore: unused_import
import 'package:intl/intl.dart' as intl;

import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for Arabic (`ar`).
class AppLocalizationsAr extends AppLocalizations {
  AppLocalizationsAr([String locale = 'ar']) : super(locale);

  @override
  String get appTitle => 'تجربة الفيديو';

  @override
  String get joinTitle => 'الدخول إلى غرفة تجريبية';

  @override
  String get provider => 'المزود';

  @override
  String get zoomWebUnavailable =>
      'Zoom لا يعمل داخل تطبيق Flutter في المتصفح. افتح صفحة Zoom للويب من الرابط نفسه.';

  @override
  String get role => 'الدور';

  @override
  String get roleTeacher => 'معلمة';

  @override
  String get roleStudent => 'طالب';

  @override
  String get roleSubstitute => 'بديلة';

  @override
  String get roleSupervisor => 'مشرفة';

  @override
  String get roleBot => 'مشارك آلي';

  @override
  String get seat => 'رقم المقعد';

  @override
  String get room => 'اسم الغرفة';

  @override
  String get device => 'اسم الجهاز';

  @override
  String get deviceHint => 'مثل ipad-safari أو android-1';

  @override
  String get network => 'الشبكة';

  @override
  String get networkNormal => 'عادية';

  @override
  String get networkLimited => 'محدودة 1.5 ميغابت';

  @override
  String get accessCode => 'رمز التجربة';

  @override
  String get serverUrl => 'عنوان الخادم';

  @override
  String get join => 'دخول';

  @override
  String autoTrials(int count) {
    return '$count محاولة تلقائية';
  }

  @override
  String get leave => 'خروج';

  @override
  String get microphone => 'الميكروفون';

  @override
  String get camera => 'الكاميرا';

  @override
  String get muteAll => 'كتم الجميع';

  @override
  String get remove => 'إخراج';

  @override
  String get lockRoom => 'قفل الغرفة';

  @override
  String get unlockRoom => 'فتح الغرفة';

  @override
  String get recordStart => 'بدء التسجيل';

  @override
  String get recordStop => 'إيقاف التسجيل';

  @override
  String quality(String quality) {
    return 'جودة الاتصال: $quality';
  }

  @override
  String get qualityExcellent => 'ممتازة';

  @override
  String get qualityGood => 'جيدة';

  @override
  String get qualityPoor => 'ضعيفة';

  @override
  String get qualityLost => 'مقطوعة';

  @override
  String get qualityUnknown => 'غير معروفة';

  @override
  String get audioOnly => 'وضع الصوت فقط';

  @override
  String joinTime(String seconds) {
    return 'زمن الدخول حتى ظهور الفيديو: $seconds ث';
  }

  @override
  String get waitingVideo => 'بانتظار الفيديو…';

  @override
  String trialProgress(int n, int total) {
    return 'المحاولة $n من $total';
  }

  @override
  String trialsDone(int ok, int total, String p50, String p95) {
    return 'انتهت المحاولات ($ok ناجحة من $total). الوسيط $p50 ث، و95% تحت $p95 ث';
  }

  @override
  String error(String message) {
    return 'خطأ: $message';
  }

  @override
  String get done => 'تم';

  @override
  String get you => 'أنت';

  @override
  String trialTimes(String token, String connected) {
    return 'رمز الدخول $token مث · الاتصال $connected مث';
  }

  @override
  String trialSeconds(int n, String seconds) {
    return '$n. $seconds ث';
  }
}
