import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/intl.dart' as intl;

import 'app_localizations_ar.dart';
import 'app_localizations_en.dart';

// ignore_for_file: type=lint

/// Callers can lookup localized strings with an instance of AppLocalizations
/// returned by `AppLocalizations.of(context)`.
///
/// Applications need to include `AppLocalizations.delegate()` in their app's
/// `localizationDelegates` list, and the locales they support in the app's
/// `supportedLocales` list. For example:
///
/// ```dart
/// import 'l10n/app_localizations.dart';
///
/// return MaterialApp(
///   localizationsDelegates: AppLocalizations.localizationsDelegates,
///   supportedLocales: AppLocalizations.supportedLocales,
///   home: MyApplicationHome(),
/// );
/// ```
///
/// ## Update pubspec.yaml
///
/// Please make sure to update your pubspec.yaml to include the following
/// packages:
///
/// ```yaml
/// dependencies:
///   # Internationalization support.
///   flutter_localizations:
///     sdk: flutter
///   intl: any # Use the pinned version from flutter_localizations
///
///   # Rest of dependencies
/// ```
///
/// ## iOS Applications
///
/// iOS applications define key application metadata, including supported
/// locales, in an Info.plist file that is built into the application bundle.
/// To configure the locales supported by your app, you’ll need to edit this
/// file.
///
/// First, open your project’s ios/Runner.xcworkspace Xcode workspace file.
/// Then, in the Project Navigator, open the Info.plist file under the Runner
/// project’s Runner folder.
///
/// Next, select the Information Property List item, select Add Item from the
/// Editor menu, then select Localizations from the pop-up menu.
///
/// Select and expand the newly-created Localizations item then, for each
/// locale your application supports, add a new item and select the locale
/// you wish to add from the pop-up menu in the Value field. This list should
/// be consistent with the languages listed in the AppLocalizations.supportedLocales
/// property.
abstract class AppLocalizations {
  AppLocalizations(String locale)
    : localeName = intl.Intl.canonicalizedLocale(locale.toString());

  final String localeName;

  static AppLocalizations of(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations)!;
  }

  static const LocalizationsDelegate<AppLocalizations> delegate =
      _AppLocalizationsDelegate();

  /// A list of this localizations delegate along with the default localizations
  /// delegates.
  ///
  /// Returns a list of localizations delegates containing this delegate along with
  /// GlobalMaterialLocalizations.delegate, GlobalCupertinoLocalizations.delegate,
  /// and GlobalWidgetsLocalizations.delegate.
  ///
  /// Additional delegates can be added by appending to this list in
  /// MaterialApp. This list does not have to be used at all if a custom list
  /// of delegates is preferred or required.
  static const List<LocalizationsDelegate<dynamic>> localizationsDelegates =
      <LocalizationsDelegate<dynamic>>[
        delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
      ];

  /// A list of this localizations delegate's supported locales.
  static const List<Locale> supportedLocales = <Locale>[
    Locale('ar'),
    Locale('en'),
  ];

  /// No description provided for @appTitle.
  ///
  /// In ar, this message translates to:
  /// **'تجربة Flutter على الويب'**
  String get appTitle;

  /// No description provided for @tabReading.
  ///
  /// In ar, this message translates to:
  /// **'النص المشكول'**
  String get tabReading;

  /// No description provided for @tabForm.
  ///
  /// In ar, this message translates to:
  /// **'النموذج'**
  String get tabForm;

  /// No description provided for @tabPayment.
  ///
  /// In ar, this message translates to:
  /// **'الدفع'**
  String get tabPayment;

  /// No description provided for @tabVideo.
  ///
  /// In ar, this message translates to:
  /// **'فيديو'**
  String get tabVideo;

  /// No description provided for @tabMeasure.
  ///
  /// In ar, this message translates to:
  /// **'القياس'**
  String get tabMeasure;

  /// No description provided for @tabFeedback.
  ///
  /// In ar, this message translates to:
  /// **'ملاحظاتك'**
  String get tabFeedback;

  /// No description provided for @webOnly.
  ///
  /// In ar, this message translates to:
  /// **'هذه الصفحة تعمل في المتصفح فقط.'**
  String get webOnly;

  /// No description provided for @showDiacritics.
  ///
  /// In ar, this message translates to:
  /// **'إظهار التشكيل'**
  String get showDiacritics;

  /// No description provided for @textSize.
  ///
  /// In ar, this message translates to:
  /// **'حجم النص'**
  String get textSize;

  /// No description provided for @textScaleNormal.
  ///
  /// In ar, this message translates to:
  /// **'عادي'**
  String get textScaleNormal;

  /// No description provided for @textScaleLarger.
  ///
  /// In ar, this message translates to:
  /// **'أكبر'**
  String get textScaleLarger;

  /// No description provided for @textScaleLargest.
  ///
  /// In ar, this message translates to:
  /// **'الأكبر'**
  String get textScaleLargest;

  /// No description provided for @fontSampleTitle.
  ///
  /// In ar, this message translates to:
  /// **'{font} بحجم {size}'**
  String fontSampleTitle(String font, int size);

  /// No description provided for @capture.
  ///
  /// In ar, this message translates to:
  /// **'التقاط صورة'**
  String get capture;

  /// No description provided for @captureSent.
  ///
  /// In ar, this message translates to:
  /// **'وصلت الصورة.'**
  String get captureSent;

  /// No description provided for @captureFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذّر إرسال الصورة: {reason}'**
  String captureFailed(String reason);

  /// No description provided for @formTitle.
  ///
  /// In ar, this message translates to:
  /// **'نموذج تجريبي'**
  String get formTitle;

  /// No description provided for @nameLabel.
  ///
  /// In ar, this message translates to:
  /// **'اسم تجريبي'**
  String get nameLabel;

  /// No description provided for @nameHint.
  ///
  /// In ar, this message translates to:
  /// **'اكتب أي اسم من خيالك، لا اسماً حقيقياً'**
  String get nameHint;

  /// No description provided for @notesLabel.
  ///
  /// In ar, this message translates to:
  /// **'ملاحظات'**
  String get notesLabel;

  /// No description provided for @listTitle.
  ///
  /// In ar, this message translates to:
  /// **'قائمة الدروس'**
  String get listTitle;

  /// No description provided for @lessonItem.
  ///
  /// In ar, this message translates to:
  /// **'الدرس {number}'**
  String lessonItem(int number);

  /// No description provided for @counterButton.
  ///
  /// In ar, this message translates to:
  /// **'اضغط هنا'**
  String get counterButton;

  /// No description provided for @counterValue.
  ///
  /// In ar, this message translates to:
  /// **'{count, plural, =0{لم تضغط بعد} =1{ضغطت مرة واحدة} =2{ضغطت مرتين} few{ضغطت {count} مرات} other{ضغطت {count} مرة}}'**
  String counterValue(int count);

  /// No description provided for @paymentIntro.
  ///
  /// In ar, this message translates to:
  /// **'صفحة دفع وهمية على موقع آخر. لا تُدفع أي أموال.'**
  String get paymentIntro;

  /// No description provided for @payRedirect.
  ///
  /// In ar, this message translates to:
  /// **'انتقال كامل إلى صفحة الدفع'**
  String get payRedirect;

  /// No description provided for @payPopup.
  ///
  /// In ar, this message translates to:
  /// **'صفحة الدفع في نافذة منبثقة'**
  String get payPopup;

  /// No description provided for @payFrame.
  ///
  /// In ar, this message translates to:
  /// **'صفحة الدفع داخل إطار'**
  String get payFrame;

  /// No description provided for @payFrameCorp.
  ///
  /// In ar, this message translates to:
  /// **'داخل إطار يسمح بالتضمين'**
  String get payFrameCorp;

  /// No description provided for @payResult.
  ///
  /// In ar, this message translates to:
  /// **'نتيجة الدفع: {status}'**
  String payResult(String status);

  /// No description provided for @payStatusPaid.
  ///
  /// In ar, this message translates to:
  /// **'تم الدفع'**
  String get payStatusPaid;

  /// No description provided for @payStatusCancelled.
  ///
  /// In ar, this message translates to:
  /// **'أُلغي الدفع'**
  String get payStatusCancelled;

  /// No description provided for @payStatusNone.
  ///
  /// In ar, this message translates to:
  /// **'لم تصل نتيجة بعد'**
  String get payStatusNone;

  /// No description provided for @payPopupNoOpener.
  ///
  /// In ar, this message translates to:
  /// **'النافذة المنبثقة لا تستطيع مخاطبة التطبيق'**
  String get payPopupNoOpener;

  /// No description provided for @payClose.
  ///
  /// In ar, this message translates to:
  /// **'إغلاق الإطار'**
  String get payClose;

  /// No description provided for @videoTargetLocal.
  ///
  /// In ar, this message translates to:
  /// **'خادم محلي'**
  String get videoTargetLocal;

  /// No description provided for @videoTargetCloud.
  ///
  /// In ar, this message translates to:
  /// **'LiveKit السحابي'**
  String get videoTargetCloud;

  /// No description provided for @videoJoin.
  ///
  /// In ar, this message translates to:
  /// **'دخول الغرفة'**
  String get videoJoin;

  /// No description provided for @videoLeave.
  ///
  /// In ar, this message translates to:
  /// **'خروج'**
  String get videoLeave;

  /// No description provided for @videoConnected.
  ///
  /// In ar, this message translates to:
  /// **'متصل'**
  String get videoConnected;

  /// No description provided for @videoConnecting.
  ///
  /// In ar, this message translates to:
  /// **'جارٍ الاتصال'**
  String get videoConnecting;

  /// No description provided for @videoDisconnected.
  ///
  /// In ar, this message translates to:
  /// **'غير متصل'**
  String get videoDisconnected;

  /// No description provided for @videoFailed.
  ///
  /// In ar, this message translates to:
  /// **'فشل الاتصال: {reason}'**
  String videoFailed(String reason);

  /// No description provided for @videoRemoteCount.
  ///
  /// In ar, this message translates to:
  /// **'{count, plural, =0{لا أحد غيرك في الغرفة} =1{مشارك واحد غيرك} =2{مشاركان غيرك} few{{count} مشاركين غيرك} other{{count} مشاركاً غيرك}}'**
  String videoRemoteCount(int count);

  /// No description provided for @videoYou.
  ///
  /// In ar, this message translates to:
  /// **'أنت'**
  String get videoYou;

  /// No description provided for @measureRuntime.
  ///
  /// In ar, this message translates to:
  /// **'طريقة التشغيل'**
  String get measureRuntime;

  /// No description provided for @measureWasm.
  ///
  /// In ar, this message translates to:
  /// **'النسخة السريعة (WebAssembly)'**
  String get measureWasm;

  /// No description provided for @measureJs.
  ///
  /// In ar, this message translates to:
  /// **'النسخة العادية (JavaScript)'**
  String get measureJs;

  /// No description provided for @measureThreads.
  ///
  /// In ar, this message translates to:
  /// **'خيوط الرسم'**
  String get measureThreads;

  /// No description provided for @measureMulti.
  ///
  /// In ar, this message translates to:
  /// **'متعددة'**
  String get measureMulti;

  /// No description provided for @measureSingle.
  ///
  /// In ar, this message translates to:
  /// **'خيط واحد'**
  String get measureSingle;

  /// No description provided for @measureIsolation.
  ///
  /// In ar, this message translates to:
  /// **'ترويستا العزل'**
  String get measureIsolation;

  /// No description provided for @measureOn.
  ///
  /// In ar, this message translates to:
  /// **'مفعّلتان'**
  String get measureOn;

  /// No description provided for @measureOff.
  ///
  /// In ar, this message translates to:
  /// **'غير مفعّلتين'**
  String get measureOff;

  /// No description provided for @measureServiceWorker.
  ///
  /// In ar, this message translates to:
  /// **'عامل الخدمة'**
  String get measureServiceWorker;

  /// No description provided for @measureSwControlled.
  ///
  /// In ar, this message translates to:
  /// **'يخدم هذه الصفحة'**
  String get measureSwControlled;

  /// No description provided for @measureSwNone.
  ///
  /// In ar, this message translates to:
  /// **'لا يخدم هذه الصفحة بعد'**
  String get measureSwNone;

  /// No description provided for @measureTimings.
  ///
  /// In ar, this message translates to:
  /// **'أزمنة هذا التحميل (ملّي ثانية)'**
  String get measureTimings;

  /// No description provided for @markSplash.
  ///
  /// In ar, this message translates to:
  /// **'ظهور الشاشة الافتتاحية'**
  String get markSplash;

  /// No description provided for @markEntrypoint.
  ///
  /// In ar, this message translates to:
  /// **'تحميل الكود'**
  String get markEntrypoint;

  /// No description provided for @markEngine.
  ///
  /// In ar, this message translates to:
  /// **'تهيئة المحرك'**
  String get markEngine;

  /// No description provided for @markFirstFrame.
  ///
  /// In ar, this message translates to:
  /// **'أول إطار'**
  String get markFirstFrame;

  /// No description provided for @markInteractive.
  ///
  /// In ar, this message translates to:
  /// **'جاهز للتفاعل'**
  String get markInteractive;

  /// No description provided for @measureBytes.
  ///
  /// In ar, this message translates to:
  /// **'حجم ما نُزِّل (كيلوبايت)'**
  String get measureBytes;

  /// No description provided for @measureVariant.
  ///
  /// In ar, this message translates to:
  /// **'النسخة الحالية'**
  String get measureVariant;

  /// No description provided for @autoRun.
  ///
  /// In ar, this message translates to:
  /// **'تشغيل القياس تلقائياً'**
  String get autoRun;

  /// No description provided for @clearCaches.
  ///
  /// In ar, this message translates to:
  /// **'مسح الذاكرة المؤقتة'**
  String get clearCaches;

  /// No description provided for @cachesCleared.
  ///
  /// In ar, this message translates to:
  /// **'مُسحت الذاكرة المؤقتة. أعد تحميل الصفحة.'**
  String get cachesCleared;

  /// No description provided for @notAvailable.
  ///
  /// In ar, this message translates to:
  /// **'غير متاح'**
  String get notAvailable;

  /// No description provided for @feedbackIntro.
  ///
  /// In ar, this message translates to:
  /// **'بعد التجربة، اختر نعم أو لا لكل سؤال ثم اضغط «إرسال».'**
  String get feedbackIntro;

  /// No description provided for @feedbackQ1.
  ///
  /// In ar, this message translates to:
  /// **'هل قرأ VoiceOver النص العربي بصوت عربي؟'**
  String get feedbackQ1;

  /// No description provided for @feedbackQ2.
  ///
  /// In ar, this message translates to:
  /// **'هل قرأ أسماء الحقول والأزرار بالعربية؟'**
  String get feedbackQ2;

  /// No description provided for @feedbackQ3.
  ///
  /// In ar, this message translates to:
  /// **'هل قرأ عناصر القائمة واحداً بعد الآخر؟'**
  String get feedbackQ3;

  /// No description provided for @feedbackQ4.
  ///
  /// In ar, this message translates to:
  /// **'هل عملت الكتابة بلوحة المفاتيح العربية دون مشاكل؟'**
  String get feedbackQ4;

  /// No description provided for @feedbackQ5.
  ///
  /// In ar, this message translates to:
  /// **'هل تحرك المؤشر صحيحاً أثناء الكتابة والتعديل؟'**
  String get feedbackQ5;

  /// No description provided for @feedbackQ6.
  ///
  /// In ar, this message translates to:
  /// **'هل ظهر التشكيل سليماً دون تداخل أو قص؟'**
  String get feedbackQ6;

  /// No description provided for @feedbackQ7.
  ///
  /// In ar, this message translates to:
  /// **'هل ثُبّت التطبيق على الشاشة الرئيسية وفُتح دون شريط المتصفح؟'**
  String get feedbackQ7;

  /// No description provided for @feedbackQ8.
  ///
  /// In ar, this message translates to:
  /// **'هل رجعت من صفحة الدفع إلى التطبيق المثبّت وظهرت النتيجة؟'**
  String get feedbackQ8;

  /// No description provided for @feedbackQ9.
  ///
  /// In ar, this message translates to:
  /// **'هل ظهر الفيديو مع ترويستي العزل؟'**
  String get feedbackQ9;

  /// No description provided for @yes.
  ///
  /// In ar, this message translates to:
  /// **'نعم'**
  String get yes;

  /// No description provided for @no.
  ///
  /// In ar, this message translates to:
  /// **'لا'**
  String get no;

  /// No description provided for @send.
  ///
  /// In ar, this message translates to:
  /// **'إرسال'**
  String get send;

  /// No description provided for @feedbackSent.
  ///
  /// In ar, this message translates to:
  /// **'وصلت إجاباتك، شكراً.'**
  String get feedbackSent;

  /// No description provided for @feedbackFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذّر الإرسال: {reason}'**
  String feedbackFailed(String reason);
}

class _AppLocalizationsDelegate
    extends LocalizationsDelegate<AppLocalizations> {
  const _AppLocalizationsDelegate();

  @override
  Future<AppLocalizations> load(Locale locale) {
    return SynchronousFuture<AppLocalizations>(lookupAppLocalizations(locale));
  }

  @override
  bool isSupported(Locale locale) =>
      <String>['ar', 'en'].contains(locale.languageCode);

  @override
  bool shouldReload(_AppLocalizationsDelegate old) => false;
}

AppLocalizations lookupAppLocalizations(Locale locale) {
  // Lookup logic when only language code is specified.
  switch (locale.languageCode) {
    case 'ar':
      return AppLocalizationsAr();
    case 'en':
      return AppLocalizationsEn();
  }

  throw FlutterError(
    'AppLocalizations.delegate failed to load unsupported locale "$locale". This is likely '
    'an issue with the localizations generation tool. Please file an issue '
    'on GitHub with a reproducible sample app and the gen-l10n configuration '
    'that was used.',
  );
}
