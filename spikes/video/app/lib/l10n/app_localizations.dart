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
  /// **'تجربة الفيديو'**
  String get appTitle;

  /// No description provided for @joinTitle.
  ///
  /// In ar, this message translates to:
  /// **'الدخول إلى غرفة تجريبية'**
  String get joinTitle;

  /// No description provided for @provider.
  ///
  /// In ar, this message translates to:
  /// **'المزود'**
  String get provider;

  /// No description provided for @zoomWebUnavailable.
  ///
  /// In ar, this message translates to:
  /// **'Zoom لا يعمل داخل تطبيق Flutter في المتصفح. افتح صفحة Zoom للويب من الرابط نفسه.'**
  String get zoomWebUnavailable;

  /// No description provided for @role.
  ///
  /// In ar, this message translates to:
  /// **'الدور'**
  String get role;

  /// No description provided for @roleTeacher.
  ///
  /// In ar, this message translates to:
  /// **'معلمة'**
  String get roleTeacher;

  /// No description provided for @roleStudent.
  ///
  /// In ar, this message translates to:
  /// **'طالب'**
  String get roleStudent;

  /// No description provided for @roleSubstitute.
  ///
  /// In ar, this message translates to:
  /// **'بديلة'**
  String get roleSubstitute;

  /// No description provided for @roleSupervisor.
  ///
  /// In ar, this message translates to:
  /// **'مشرفة'**
  String get roleSupervisor;

  /// No description provided for @roleBot.
  ///
  /// In ar, this message translates to:
  /// **'مشارك آلي'**
  String get roleBot;

  /// No description provided for @seat.
  ///
  /// In ar, this message translates to:
  /// **'رقم المقعد'**
  String get seat;

  /// No description provided for @room.
  ///
  /// In ar, this message translates to:
  /// **'اسم الغرفة'**
  String get room;

  /// No description provided for @device.
  ///
  /// In ar, this message translates to:
  /// **'اسم الجهاز'**
  String get device;

  /// No description provided for @deviceHint.
  ///
  /// In ar, this message translates to:
  /// **'مثل ipad-safari أو android-1'**
  String get deviceHint;

  /// No description provided for @network.
  ///
  /// In ar, this message translates to:
  /// **'الشبكة'**
  String get network;

  /// No description provided for @networkNormal.
  ///
  /// In ar, this message translates to:
  /// **'عادية'**
  String get networkNormal;

  /// No description provided for @networkLimited.
  ///
  /// In ar, this message translates to:
  /// **'محدودة 1.5 ميغابت'**
  String get networkLimited;

  /// No description provided for @accessCode.
  ///
  /// In ar, this message translates to:
  /// **'رمز التجربة'**
  String get accessCode;

  /// No description provided for @serverUrl.
  ///
  /// In ar, this message translates to:
  /// **'عنوان الخادم'**
  String get serverUrl;

  /// No description provided for @join.
  ///
  /// In ar, this message translates to:
  /// **'دخول'**
  String get join;

  /// No description provided for @autoTrials.
  ///
  /// In ar, this message translates to:
  /// **'{count} محاولة تلقائية'**
  String autoTrials(int count);

  /// No description provided for @leave.
  ///
  /// In ar, this message translates to:
  /// **'خروج'**
  String get leave;

  /// No description provided for @microphone.
  ///
  /// In ar, this message translates to:
  /// **'الميكروفون'**
  String get microphone;

  /// No description provided for @camera.
  ///
  /// In ar, this message translates to:
  /// **'الكاميرا'**
  String get camera;

  /// No description provided for @muteAll.
  ///
  /// In ar, this message translates to:
  /// **'كتم الجميع'**
  String get muteAll;

  /// No description provided for @remove.
  ///
  /// In ar, this message translates to:
  /// **'إخراج'**
  String get remove;

  /// No description provided for @lockRoom.
  ///
  /// In ar, this message translates to:
  /// **'قفل الغرفة'**
  String get lockRoom;

  /// No description provided for @unlockRoom.
  ///
  /// In ar, this message translates to:
  /// **'فتح الغرفة'**
  String get unlockRoom;

  /// No description provided for @recordStart.
  ///
  /// In ar, this message translates to:
  /// **'بدء التسجيل'**
  String get recordStart;

  /// No description provided for @recordStop.
  ///
  /// In ar, this message translates to:
  /// **'إيقاف التسجيل'**
  String get recordStop;

  /// No description provided for @quality.
  ///
  /// In ar, this message translates to:
  /// **'جودة الاتصال: {quality}'**
  String quality(String quality);

  /// No description provided for @qualityExcellent.
  ///
  /// In ar, this message translates to:
  /// **'ممتازة'**
  String get qualityExcellent;

  /// No description provided for @qualityGood.
  ///
  /// In ar, this message translates to:
  /// **'جيدة'**
  String get qualityGood;

  /// No description provided for @qualityPoor.
  ///
  /// In ar, this message translates to:
  /// **'ضعيفة'**
  String get qualityPoor;

  /// No description provided for @qualityLost.
  ///
  /// In ar, this message translates to:
  /// **'مقطوعة'**
  String get qualityLost;

  /// No description provided for @qualityUnknown.
  ///
  /// In ar, this message translates to:
  /// **'غير معروفة'**
  String get qualityUnknown;

  /// No description provided for @audioOnly.
  ///
  /// In ar, this message translates to:
  /// **'وضع الصوت فقط'**
  String get audioOnly;

  /// No description provided for @joinTime.
  ///
  /// In ar, this message translates to:
  /// **'زمن الدخول حتى ظهور الفيديو: {seconds} ث'**
  String joinTime(String seconds);

  /// No description provided for @waitingVideo.
  ///
  /// In ar, this message translates to:
  /// **'بانتظار الفيديو…'**
  String get waitingVideo;

  /// No description provided for @trialProgress.
  ///
  /// In ar, this message translates to:
  /// **'المحاولة {n} من {total}'**
  String trialProgress(int n, int total);

  /// No description provided for @trialsDone.
  ///
  /// In ar, this message translates to:
  /// **'انتهت المحاولات ({ok} ناجحة من {total}). الوسيط {p50} ث، و95% تحت {p95} ث'**
  String trialsDone(int ok, int total, String p50, String p95);

  /// No description provided for @error.
  ///
  /// In ar, this message translates to:
  /// **'خطأ: {message}'**
  String error(String message);

  /// No description provided for @done.
  ///
  /// In ar, this message translates to:
  /// **'تم'**
  String get done;

  /// No description provided for @you.
  ///
  /// In ar, this message translates to:
  /// **'أنت'**
  String get you;

  /// No description provided for @trialTimes.
  ///
  /// In ar, this message translates to:
  /// **'رمز الدخول {token} مث · الاتصال {connected} مث'**
  String trialTimes(String token, String connected);

  /// No description provided for @trialSeconds.
  ///
  /// In ar, this message translates to:
  /// **'{n}. {seconds} ث'**
  String trialSeconds(int n, String seconds);
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
