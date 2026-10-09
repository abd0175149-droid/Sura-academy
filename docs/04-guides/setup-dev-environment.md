# إعداد بيئة التطوير

## الأدوات المطلوبة

| الأداة | الإصدار | لماذا |
| --- | --- | --- |
| Git + GitHub CLI (`gh`) | الأحدث | المستودع وطلبات الدمج |
| Docker Desktop | الأحدث | PostgreSQL وRedis وMailpit وMinIO وGotenberg وClamAV محلياً |
| PHP | 8.4 مع امتدادات pgsql وredis وintl وgd/imagick وbcmath وpcov | الخادم، والـ hooks، والسكربتات |
| Composer | 2.x | حزم PHP |
| Node.js | LTS الحالي | Vite وTailwind وRedocly وPlaywright MCP |
| Flutter | المستقر (3.44 أو أحدث) ويشمل Dart | تطبيقا الأسرة والمعلمة |
| Google Chrome | الأحدث | تشغيل Flutter على الويب واختباره |
| VS Code + إضافة Claude Code | الأحدث | بيئة العمل |

**Windows:** Claude Code يعمل مع Git Bash، وسكربتات `scripts/*.sh` تحتاج bash. للخادم يُنصح بـ WSL2 مع Docker Desktop. تأكد أن الأمر `php` متاح في الطرفية التي يعمل فيها Claude، لأن الـ hooks تستدعيه.

## بيئة سُرى المعتمدة: WSL2 على Windows (D-33)

التطوير كله على جهاز Windows داخل توزيعة WSL مخصصة لسُرى، والكود ينتقل إلى خادم المعاينة عبر GitHub (`docs/04-guides/server-environment.md`).

| البند | القيمة |
| --- | --- |
| التوزيعة | `Ubuntu-24.04` في WSL2، مطابقة لنظام الخادم، وهي التوزيعة الافتراضية |
| المستخدم | `abdul` بلا كلمة مرور. أوامر root تُشغَّل من Windows فقط: `wsl -d Ubuntu-24.04 -u root` |
| المستودع | `~/sura-academy` داخل WSL، لا تحت `/mnt/c`، بنهايات أسطر LF |
| PHP | 8.4 من مستودع ondrej، بالامتدادات كلها ومعها pcov وredis وpcntl وposix |
| الأدوات | Composer 2 في `~/.local/bin`، وNode 24 عبر nvm، وintelephense، وFlutter stable في `~/development/flutter`، وGoogle Chrome، ومتصفحات Playwright، وgh، وClaude Code مع إضافتي `dart-flutter` و`php-lsp` |
| Docker | Docker Desktop على Windows، مع تكامل WSL للتوزيعة الافتراضية |

طريقة العمل:
1. في VS Code على Windows: F1 ثم `WSL: Connect to WSL`، ثم Open Folder ثم `/home/abdul/sura-academy`. أو من الطرفية: `code --remote wsl+Ubuntu-24.04 /home/abdul/sura-academy`.
2. ثبّت إضافة Claude Code داخل WSL حين يعرضها VS Code، وسجّل الدخول، ووافق على الثقة بالمجلد وعلى خوادم MCP.
3. ابدأ الحزمة بـ `/sura-wp WP-x.y` من جذر المستودع.

لا يُمس على Windows: PHP في Laragon، و`C:\flutter`، وNode، وهوية git العامة.

## Claude Code

1. ثبّت Claude Code وسجّل الدخول (`claude`).
2. إضافة Flutter الرسمية (مهارات Flutter وDart وخادم MCP الخاص بهما):
   ```bash
   claude plugin marketplace add flutter/agent-plugins
   claude plugin install dart-flutter@dart-flutter
   ```
3. ذكاء الكود لـ PHP داخل Claude Code:
   ```bash
   npm install -g intelephense
   ```
   ثم داخل جلسة Claude: `/plugin install php-lsp@claude-plugins-official`
4. Context7 (توثيق محدث للمكتبات): أنشئ مفتاحاً من موقع Context7، ثم أضف إلى ملف إعداد الطرفية:
   ```bash
   export CONTEXT7_API_KEY="..."
   ```
   `.mcp.json` يقرأ المفتاح من البيئة، فلا يُكتب في المستودع.
5. Laravel Boost: يُثبَّت داخل WP-0.1 بعد إنشاء `apps/api`:
   ```bash
   cd apps/api && composer require laravel/boost --dev && php artisan boost:install
   ```
   اختر Claude Code. الخادم معرّف مسبقاً في `.mcp.json` الجذري.
6. Playwright MCP: معرّف في `.mcp.json` ويعمل عبر `npx` (يحتاج Node).
7. اختياري لاحقاً لتحسين مهارات المشروع وقياسها: `/plugin install skill-creator@claude-plugins-official`

## التحقق من البيئة (جزء من WP-0.2)

داخل جلسة Claude:
- `/mcp`: الخوادم context7 وlaravel-boost (بعد WP-0.1) وplaywright وdart متصلة.
- `/skills`: تظهر مهارات المشروع الاثنتا عشرة.
- `/agents`: يظهر الوكلاء الستة.
- `/context`: يظهر `CLAUDE.md` تحت Memory files.
- `/doctor`: لا أخطاء إعداد.

## الخدمات المحلية

```bash
docker compose -f infra/docker/compose.yaml up -d
docker compose -f infra/docker/compose.yaml ps
```

| الخدمة | المنفذ المحلي | الاستخدام |
| --- | --- | --- |
| PostgreSQL 17 | 5432 | قاعدة البيانات |
| Redis 7 | 6379 | الطوابير والكاش وجلسات الويب |
| Mailpit | 8025 (واجهة) | معاينة البريد |
| MinIO | 9001 (واجهة) | بديل S3 |
| Gotenberg | 3000 | توليد PDF |
| ClamAV | 3310 | فحص الملفات |

يُنشئ WP-0.1 ملف compose وقيمه الدقيقة.

## الأسرار

لا سر في المستودع أبداً. محلياً في `apps/api/.env` (مستثنى من git ومحجوب عن Claude). في التجربة والإنتاج في AWS Secrets Manager. مفاتيح PayTabs ومزود الفيديو التجريبية تضعها أنت بنفسك في `.env` المحلي.
