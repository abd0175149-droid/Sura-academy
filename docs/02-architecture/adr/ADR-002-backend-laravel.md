# ADR-002: الخادم Laravel مع Filament والموقع العام بـ Blade

- الحالة: Accepted · التاريخ: 2026-10-08 · المصدر: `spec/13-architecture-original.md` (المراجعة 24)، D-21

## القرار
| الطبقة | الاختيار |
| --- | --- |
| الإطار | Laravel 13.x على PHP 8.4 |
| قاعدة البيانات | PostgreSQL 17 أو أحدث |
| الطوابير | Redis + Laravel Horizon؛ الجدولة بـ Laravel Scheduler |
| المصادقة | Laravel Fortify (تسجيل، دخول، تحقق ثنائي TOTP) + Sanctum (SPA وملفات ارتباط، ورموز للهاتف لاحقاً) |
| الصلاحيات | spatie/laravel-permission + Policies + نطاقات استعلام |
| لوحة الإدارة | Filament (أحدث إصدار رئيسي مستقر تغطيه إرشادات Laravel Boost، يُثبَّت في WP-0.1) |
| الموقع العام | Blade + Tailwind CSS بالخصائص المنطقية + Vite |
| أعلام الميزات | Laravel Pennant |
| Webhooks الواردة | spatie/laravel-webhook-client |
| المال | brick/money داخل كائنات قيمة خاصة بنا |
| الهاتف | propaganistas/laravel-phone (E.164) |
| PDF | Gotenberg عبر gotenberg/gotenberg-php |
| الاختبار | Pest (+ arch) · Larastan · Pint |
| المراقبة | sentry/sentry-laravel |
| أدوات الوكيل | Laravel Boost (خادم MCP وإرشادات) |

## قواعد تثبيت الإصدارات
WP-0.1 يثبّت الإصدارات الدقيقة في `composer.lock` ويسجلها في جدول «الإصدارات المثبتة» أدناه. أي حزمة غير مذكورة هنا تحتاج إذن صاحب المشروع قبل `composer require`.

## الإصدارات المثبتة
| الحزمة | الإصدار | تاريخ التثبيت |
| --- | --- | --- |
| (يملؤها WP-0.1) | | |
