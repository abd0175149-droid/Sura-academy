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
الحزم التي يأتي بها هيكل Laravel نفسه (`laravel/tinker`، `laravel/pail`، `laravel/pao`، `fakerphp/faker`، `mockery/mockery`، `nunomaduro/collision`، `concurrently`) بقيت كما هي. القيم الدقيقة في `apps/api/composer.lock` و`apps/api/package-lock.json`.

| الحزمة | الإصدار | تاريخ التثبيت |
| --- | --- | --- |
| PHP | 8.4.26 (المتطلب `^8.4`) | 2026-10-11 |
| `laravel/framework` | 13.35.0 | 2026-10-11 |
| `laravel/horizon` | 5.50.0 | 2026-10-11 |
| `laravel/fortify` | 1.41.0 (المسارات معطلة حتى WP-1.2) | 2026-10-11 |
| `laravel/sanctum` | 4.3.3 | 2026-10-11 |
| `spatie/laravel-permission` | 8.3.0 | 2026-10-11 |
| `filament/filament` | 5.10.1 (أحدث رئيسي مستقر، وإرشاداته لـ Boost مضمّنة في الحزمة) | 2026-10-11 |
| `laravel/pennant` | 1.26.0 | 2026-10-11 |
| `spatie/laravel-webhook-client` | 3.7.0 | 2026-10-11 |
| `brick/money` | 0.15.2 | 2026-10-11 |
| `propaganistas/laravel-phone` | 6.1.0 | 2026-10-11 |
| `gotenberg/gotenberg-php` | 2.25.0 | 2026-10-11 |
| `sentry/sentry-laravel` | 4.29.0 | 2026-10-11 |
| `pestphp/pest (+ arch 5.0.0) · pestphp/pest-plugin-laravel` | 5.3.1 · 5.0.1 | 2026-10-11 |
| `larastan/larastan` | 3.13.0 | 2026-10-11 |
| `laravel/pint` | 1.32.1 | 2026-10-11 |
| `laravel/boost` | 2.10.3 | 2026-10-11 |
| `tailwindcss · @tailwindcss/vite` | 4.3.3 | 2026-10-11 |
| `vite · laravel-vite-plugin` | 8.3.4 · 3.2.0 | 2026-10-11 |
