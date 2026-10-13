// ignore: unused_import
import 'package:intl/intl.dart' as intl;

import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for Arabic (`ar`).
class AppLocalizationsAr extends AppLocalizations {
  AppLocalizationsAr([String locale = 'ar']) : super(locale);

  @override
  String get appTitle => 'تجربة Flutter على الويب';

  @override
  String get tabReading => 'النص المشكول';

  @override
  String get tabForm => 'النموذج';

  @override
  String get tabPayment => 'الدفع';

  @override
  String get tabVideo => 'فيديو';

  @override
  String get tabMeasure => 'القياس';

  @override
  String get tabFeedback => 'ملاحظاتك';

  @override
  String get webOnly => 'هذه الصفحة تعمل في المتصفح فقط.';

  @override
  String get showDiacritics => 'إظهار التشكيل';

  @override
  String get textSize => 'حجم النص';

  @override
  String get textScaleNormal => 'عادي';

  @override
  String get textScaleLarger => 'أكبر';

  @override
  String get textScaleLargest => 'الأكبر';

  @override
  String fontSampleTitle(String font, int size) {
    return '$font بحجم $size';
  }

  @override
  String get capture => 'التقاط صورة';

  @override
  String get captureSent => 'وصلت الصورة.';

  @override
  String captureFailed(String reason) {
    return 'تعذّر إرسال الصورة: $reason';
  }

  @override
  String get formTitle => 'نموذج تجريبي';

  @override
  String get nameLabel => 'اسم تجريبي';

  @override
  String get nameHint => 'اكتب أي اسم من خيالك، لا اسماً حقيقياً';

  @override
  String get notesLabel => 'ملاحظات';

  @override
  String get listTitle => 'قائمة الدروس';

  @override
  String lessonItem(int number) {
    return 'الدرس $number';
  }

  @override
  String get counterButton => 'اضغط هنا';

  @override
  String counterValue(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: 'ضغطت $count مرة',
      few: 'ضغطت $count مرات',
      two: 'ضغطت مرتين',
      one: 'ضغطت مرة واحدة',
      zero: 'لم تضغط بعد',
    );
    return '$_temp0';
  }

  @override
  String get paymentIntro => 'صفحة دفع وهمية على موقع آخر. لا تُدفع أي أموال.';

  @override
  String get payRedirect => 'انتقال كامل إلى صفحة الدفع';

  @override
  String get payPopup => 'صفحة الدفع في نافذة منبثقة';

  @override
  String get payFrame => 'صفحة الدفع داخل إطار';

  @override
  String get payFrameCorp => 'داخل إطار يسمح بالتضمين';

  @override
  String payResult(String status) {
    return 'نتيجة الدفع: $status';
  }

  @override
  String get payStatusPaid => 'تم الدفع';

  @override
  String get payStatusCancelled => 'أُلغي الدفع';

  @override
  String get payStatusNone => 'لم تصل نتيجة بعد';

  @override
  String get payPopupNoOpener => 'النافذة المنبثقة لا تستطيع مخاطبة التطبيق';

  @override
  String get payClose => 'إغلاق الإطار';

  @override
  String get videoTargetLocal => 'خادم محلي';

  @override
  String get videoTargetCloud => 'LiveKit السحابي';

  @override
  String get videoJoin => 'دخول الغرفة';

  @override
  String get videoLeave => 'خروج';

  @override
  String get videoConnected => 'متصل';

  @override
  String get videoConnecting => 'جارٍ الاتصال';

  @override
  String get videoDisconnected => 'غير متصل';

  @override
  String videoFailed(String reason) {
    return 'فشل الاتصال: $reason';
  }

  @override
  String videoRemoteCount(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: '$count مشاركاً غيرك',
      few: '$count مشاركين غيرك',
      two: 'مشاركان غيرك',
      one: 'مشارك واحد غيرك',
      zero: 'لا أحد غيرك في الغرفة',
    );
    return '$_temp0';
  }

  @override
  String get videoYou => 'أنت';

  @override
  String get measureRuntime => 'طريقة التشغيل';

  @override
  String get measureWasm => 'النسخة السريعة (WebAssembly)';

  @override
  String get measureJs => 'النسخة العادية (JavaScript)';

  @override
  String get measureThreads => 'خيوط الرسم';

  @override
  String get measureMulti => 'متعددة';

  @override
  String get measureSingle => 'خيط واحد';

  @override
  String get measureIsolation => 'ترويستا العزل';

  @override
  String get measureOn => 'مفعّلتان';

  @override
  String get measureOff => 'غير مفعّلتين';

  @override
  String get measureServiceWorker => 'عامل الخدمة';

  @override
  String get measureSwControlled => 'يخدم هذه الصفحة';

  @override
  String get measureSwNone => 'لا يخدم هذه الصفحة بعد';

  @override
  String get measureTimings => 'أزمنة هذا التحميل (ملّي ثانية)';

  @override
  String get markSplash => 'ظهور الشاشة الافتتاحية';

  @override
  String get markEntrypoint => 'تحميل الكود';

  @override
  String get markEngine => 'تهيئة المحرك';

  @override
  String get markFirstFrame => 'أول إطار';

  @override
  String get markInteractive => 'جاهز للتفاعل';

  @override
  String get measureBytes => 'حجم ما نُزِّل (كيلوبايت)';

  @override
  String get measureVariant => 'النسخة الحالية';

  @override
  String get autoRun => 'تشغيل القياس تلقائياً';

  @override
  String get clearCaches => 'مسح الذاكرة المؤقتة';

  @override
  String get cachesCleared => 'مُسحت الذاكرة المؤقتة. أعد تحميل الصفحة.';

  @override
  String get notAvailable => 'غير متاح';

  @override
  String get feedbackIntro =>
      'بعد التجربة، اختر نعم أو لا لكل سؤال ثم اضغط «إرسال».';

  @override
  String get feedbackQ1 => 'هل قرأ VoiceOver النص العربي بصوت عربي؟';

  @override
  String get feedbackQ2 => 'هل قرأ أسماء الحقول والأزرار بالعربية؟';

  @override
  String get feedbackQ3 => 'هل قرأ عناصر القائمة واحداً بعد الآخر؟';

  @override
  String get feedbackQ4 => 'هل عملت الكتابة بلوحة المفاتيح العربية دون مشاكل؟';

  @override
  String get feedbackQ5 => 'هل تحرك المؤشر صحيحاً أثناء الكتابة والتعديل؟';

  @override
  String get feedbackQ6 => 'هل ظهر التشكيل سليماً دون تداخل أو قص؟';

  @override
  String get feedbackQ7 =>
      'هل ثُبّت التطبيق على الشاشة الرئيسية وفُتح دون شريط المتصفح؟';

  @override
  String get feedbackQ8 =>
      'هل رجعت من صفحة الدفع إلى التطبيق المثبّت وظهرت النتيجة؟';

  @override
  String get feedbackQ9 => 'هل ظهر الفيديو مع ترويستي العزل؟';

  @override
  String get yes => 'نعم';

  @override
  String get no => 'لا';

  @override
  String get send => 'إرسال';

  @override
  String get feedbackSent => 'وصلت إجاباتك، شكراً.';

  @override
  String feedbackFailed(String reason) {
    return 'تعذّر الإرسال: $reason';
  }

  @override
  String get listenTitle => 'استمع (تجربة)';

  @override
  String get listenNoVoice => 'لا يوجد صوت عربي على هذا الجهاز.';

  @override
  String get listenOnDevice => 'على الجهاز';

  @override
  String get listenOnline => 'عبر الإنترنت';

  @override
  String get listenStory => 'استمع إلى القصة';

  @override
  String get listenStoryPlain => 'القصة دون تشكيل';

  @override
  String get listenWords => 'كلمات صعبة';

  @override
  String get listenClassTime => 'موعد الحصة';

  @override
  String get listenStop => 'إيقاف';

  @override
  String get feedbackQ10 => 'هل كان صوت «استمع» واضحاً ومفهوماً؟';

  @override
  String get feedbackQ11 => 'هل نطق الكلمات المشكولة صحيحاً؟';

  @override
  String get feedbackQ12 => 'هل قرأ موعد الحصة والرابط بشكل مفهوم؟';
}
