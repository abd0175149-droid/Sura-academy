# بيئة خادم التطوير

هذا الدليل يصف كل ما يخص سُرى على خادم التطوير `mafia-prod`. الخادم مؤقت ومشترك مع مشاريع أخرى، ولا يُستخدم للنشر الفعلي ولا يحمل بيانات حقيقية (القرار D-32)، لكنه يُبنى كنشر كامل لغرض الاختبار. التطوير نفسه يجري على جهاز Windows داخل WSL، والخادم هدف النشر التجريبي الذي يُحدَّث من GitHub (القرار D-33). جرد الخادم الكامل، بما فيه المشاريع الأخرى، محفوظ على الخادم خارج المستودع في `~/sura-ops/server-inventory.md`.

آخر تحديث: 2026-10-09 (جلسة التحضير الأولى).

## 1. الخادم باختصار

| البند | القيمة |
| --- | --- |
| النظام | Ubuntu 24.04.4 LTS، النواة 6.17، `x86_64` |
| العتاد | حاسوب محمول، 4 أنوية و8 خيوط، ذاكرة 15 GiB |
| الذاكرة المتاحة | 5.1 GiB كحد أدنى خلال 24 ساعة، و7.2 GiB في المتوسط، والـ swap ممتلئ |
| القرص | نحو 66 GB متاحة، والاستخدام قرب 87% |
| المستخدم | `sysadmin`، وعضويته في مجموعة `docker` تعادل صلاحيات root |
| المستودع | `/home/sysadmin/sura-academy` |
| المنطقة الزمنية | `Asia/Amman`، والتطبيق يعمل بتوقيت UTC دائماً |

## 2. ما ثُبّت لسُرى

| الأداة | الإصدار | المسار | ملاحظات |
| --- | --- | --- | --- |
| PHP | 8.4.19 (من CloudPanel) | `~/.local/share/sura/bin/php` ← `/usr/bin/php8.4` | `php` الافتراضي للنظام لم يتغيّر، والرابط يثبّت 8.4 لسُرى حتى لو تغيّر الافتراضي |
| امتدادات PHP | `pgsql` و`pdo_pgsql` و`pcov` 1.0.12 | `~/.local/share/sura/php/ext/` | تُحمَّل عبر `PHP_INI_SCAN_DIR` في بيئة سُرى فقط، ومعها `opcache.jit=disable` لأن JIT لا يعمل مع pcov. البقية (redis وintl وmbstring وxml وcurl وzip وbcmath وgd وpcntl وposix) موجودة في PHP النظام |
| Composer | 2.9.5 | `/usr/local/bin/composer` | مشترك، فلا يُحدَّث |
| Node.js | 24.21.0 ومعه npm 11.19.0 | `~/.nvm` (nvm 0.40.8) | الافتراضي للنظام يبقى v20.20.2، و`~/.bashrc` لم يُعدَّل |
| intelephense | 1.18.5 | تحت Node 24 | تستخدمه إضافة `php-lsp` |
| Flutter وDart | 3.47.7 و3.13.5 | `~/development/flutter` | التحليلات معطّلة، والويب مفعّل، وسطح مكتب Linux معطّل. لا Android SDK ولا Xcode |
| Google Chrome | 155 | `/usr/bin/google-chrome` | يستخدمه Flutter وخادم Playwright MCP، وFirefox بقي المتصفح الافتراضي |
| متصفحات Playwright | Playwright 1.64.0 | `~/.cache/ms-playwright` | Chromium 1248 ونسخته دون واجهة وffmpeg، و`@playwright/mcp` 0.0.83 مخزّن في ذاكرة npx |
| gh | 2.102.0 | `~/.local/bin/gh` | رمز fine-grained مقصور على المستودع |
| Claude Code | 2.1.295 | `~/.local/bin/claude` | إضافتا `dart-flutter` 1.0.6 و`php-lsp` 1.0.0 |
| Docker وCompose | 29.4.0 وv5.1.2 | النظام | لم يُمَسّا |
| cloudflared | 2026.1.2 | النظام | النفق مشترك ولم يُمَس |

## 3. العمل اليومي

التطوير وجلسات Claude تجري على جهاز Windows داخل WSL (القرار D-33، والتفاصيل في `setup-dev-environment.md`). الكود ينتقل إلى هذا الخادم عبر GitHub فقط:

1. تُدمج التغييرات في `main` عبر طلب دمج.
2. على الخادم: `ssh mafia-prod` ثم `cd ~/sura-academy && git pull --ff-only`.
3. تُشغَّل خدمات المعاينة أو تُحدَّث حسب ما تحدده WP-1.8.

إن احتجت جلسة Claude على الخادم نفسه، للتشخيص مثلاً:

- شغّل Claude بالأمر `sura-claude`: يدخل جذر المستودع ويعمل داخل ميزانية سُرى. أو افتح المجلد `/home/sysadmin/sura-academy` نفسه في VS Code Remote-SSH، والإعداد `claudeCode.claudeProcessWrapper` يمرّر الإضافة عبر الغلاف ذاته.
- لا تبدأ Claude من مجلد فرعي ولا تستخدم `/cd`، لأن الـ hooks وصلاحيات المشروع تُحمَّل من جذر المستودع فقط.
- لبيئة سُرى في طرفيتك: `source ~/.config/sura/env.sh`.
- المهام الثقيلة عبر `sura-job`، مثل `sura-job bash scripts/verify.sh --changed`. سقفها 2G، ويُغيَّر بالمتغير `SURA_JOB_MEM`.
- على هذا الخادم: `php artisan test --parallel --processes=2`، و`flutter test -j 2`، وبناء `flutter build web` واحد في كل مرة. التغطية الكاملة وبناء `--wasm` تعمل في GitHub Actions.
- الملفان `.claude/settings.local.json` و`CLAUDE.local.md` في جذر المستودع خاصان بهذا الخادم ومستثنيان من git.

## 4. حماية المشاريع الأخرى

| الطبقة | المكان | ما تفعله |
| --- | --- | --- |
| سياسة Claude | `/etc/claude-code/managed-settings.json` (root) | الوضع العادي، وتعطيل تجاوز الأذونات، ومنع القراءة خارج المشروع، ومنع أوامر docker الهدّامة وsudo وsystemctl وcrontab وsysctl وapt وcloudflared و`gh auth token`، ومنع قراءة مسارات الاعتماد |
| حارس الأوامر | `/etc/claude-code/hooks/sura_host_guard.php` (root) | يفحص كل أمر Bash: docker لمشروع `sura-academy` أو `sura-<spike>` فقط، ولا حذف أو كتابة خارج مسارات سُرى، ولا لمس لملفات الاعتماد أو ملفات `.env` للمشاريع الأخرى. اجتاز 66 حالة اختبار. يلتقط الأخطاء ولا يصد مهاجماً متعمداً |
| ميزانية الموارد | `~/.config/systemd/user/sura.slice` | ذاكرة 2560M مرنة و3G قصوى دون swap، ومعالج 200%. عمليات سُرى بأولوية قتل 800، و`sura-job` بأولوية 1000، فتُوقَف قبل الإنتاج عند نفاد الذاكرة |
| تثبيت المنافذ | `~/.config/sura/env.sh` و`.claude/settings.local.json` | `DB_PORT=25432` و`REDIS_PORT=26379` و`REDIS_PREFIX=sura-` و`MAIL_PORT=21025` و`SERVER_PORT=28000` متغيرات بيئة حقيقية تتغلب على `.env`، فلا يصل Laravel إلى Redis الإنتاج على 6379 |

إذا امتلأ القرص أو الذاكرة فتوقّف وأبلغ صاحب المشروع. لا `docker system prune` ولا حذف لذاكرة البناء، فهي لمشاريع أخرى.

## 5. المنافذ المحجوزة لسُرى

كل المنافذ على `127.0.0.1` فقط. القاعدة: الرقم 2 أمام المنفذ الافتراضي حيث أمكن.

| الخدمة | داخل الحاوية | على الخادم |
| --- | --- | --- |
| PostgreSQL 17 | 5432 | 25432 |
| Redis 7 | 6379 | 26379 |
| Mailpit SMTP | 1025 | 21025 |
| Mailpit (الواجهة) | 8025 | 28025 |
| MinIO (S3) | 9000 | 29000 |
| MinIO (اللوحة) | 9001 | 29001 |
| Gotenberg | 3000 | 23000 |
| ClamAV | 3310 | 23310 |
| Laravel: الـ API والموقع و`/admin` | — | 28000 |
| Vite | — | 25173 |
| Flutter `web-server` أثناء التطوير | — | 27000 |
| تطبيق الأسرة (بناء الويب) | — | 28081 |
| بوابة المعلمة (بناء الويب) | — | 28082 |

ممنوع على هذا الخادم: 5432 و6379 و3000 و3310 و8000 لأنها مشغولة، وأي ربط على `0.0.0.0`. تحقق جدول المنافذ في 2026-10-09 من المنافذ المستمعة ومن منافذ كل الحاويات، المتوقفة منها أيضاً.

## 6. قواعد ملزمة لـ `infra/docker/compose.yaml`

- السطر الأول `name: sura-academy`. لا `container_name`، ولا `name:` أو `external: true` للأحجام والشبكات، لأن هذه الأسماء عامة على الخادم.
- شبكة صريحة بالنطاق `10.213.27.0/24`، لأن نطاقات Docker الافتراضية نفدت على هذا الخادم.
- كل منفذ منشور على `127.0.0.1` من جدول المنافذ.
- لكل خدمة `mem_limit` و`cpus` و`restart: "no"` و`oom_score_adj: 800`، وسجلات `json-file` بحد `10m` وثلاثة ملفات. الميزانية التقريبية: postgres 512m، وredis 128m، وminio 256m، وmailpit 64m.
- ClamAV وGotenberg في `profiles` عند الطلب (`scan` و`pdf`). ClamAV بحد 2g ومعه `ConcurrentDatabaseReload no`، وGotenberg بحد 1g.
- لـ Redis سُرى كلمة مرور (`requirepass`)، ولـ Postgres أدوار سُرى الخاصة (`sura_app` و`sura_migrator`).
- البريد عبر Mailpit أو `log` فقط، ولا يُستخدم المنفذ 25 لأنه بريد الإنتاج.
- إذا أظهر `up` رسالة «orphan containers» فتوقّف وأبلغ صاحب المشروع، ولا تستخدم `--remove-orphans`.

## 7. المضيفات ونفق Cloudflare

**كيف يعمل النفق:** خدمة `cloudflared` واحدة على الخادم بإعداد محلي في `/etc/cloudflared/config.yml` تخدم كل المشاريع. يستقبل Cloudflare الطلب على المضيف، ويمرّره عبر النفق إلى منفذ محلي حسب قواعد `ingress` بالترتيب، وآخر قاعدة ترد 404. لا يُفتح أي منفذ على الإنترنت، ولا يتغيّر إعداد النفق إلا بيد صاحب المشروع.

كل مضيفات سُرى تحت `masaros.net` بمستوى فرعي واحد، لتغطيها شهادة Cloudflare المجانية.

| المضيف | يتجه إلى | الحماية |
| --- | --- | --- |
| `sura.masaros.net` | Laravel على 28000 (الموقع العام) | Access |
| `sura-api.masaros.net` | Laravel على 28000 (الـ API و`/admin` و`/webhooks/*`) | Access، ما عدا `/webhooks/*` |
| `sura-app.masaros.net` | تطبيق الأسرة على 28081 | Access |
| `sura-teacher.masaros.net` | بوابة المعلمة على 28082 | Access |
| `sura-files.masaros.net` | MinIO على 29000 للروابط الموقّعة | Bypass، فالتوقيع يحمي الروابط |
| `sura-mail.masaros.net` | Mailpit على 28025 | Access |

**ملفات الارتباط:** الواجهات على مضيفات مختلفة تحت `masaros.net`، لذلك:
- `SESSION_DOMAIN=.masaros.net` و`SANCTUM_STATEFUL_DOMAINS=sura.masaros.net,sura-app.masaros.net,sura-teacher.masaros.net`، وCORS مع `supports_credentials`.
- لأن `masaros.net` يخدم مشاريع أخرى: اسم ملف الجلسة `SESSION_COOKIE=sura_session`، واسم خاص لملف XSRF يُضبط في WP-1.2، حتى لا تتصادم مع ملفات تلك المشاريع.
- البديل الأنظف إن احتجته لاحقاً: `sura.masaros.net` نطاقاً خاصاً بسُرى بمضيفات من مستوى ثانٍ مثل `api.sura.masaros.net`، ويحتاج Advanced Certificate Manager من Cloudflare.

**Laravel خلف النفق:** `TRUSTED_PROXIES=127.0.0.1`، و`APP_URL=https://sura-api.masaros.net`.

### 7.1 ما يطبّقه صاحب المشروع

1. **سجلات DNS** (Cloudflare ← `masaros.net` ← DNS): لـ `masaros.net` سجل عام `*` يخدم تطبيقاً آخر، لذلك أنشئ لكل مضيف أعلاه سجل `CNAME` صريحاً بوضع Proxied، هدفه `<tunnel-id>.cfargotunnel.com` الخاص بنفق هذا الخادم (انسخه من سجل قائم يشير إلى النفق نفسه). السجل الصريح لا يغيّر سلوك بقية الأسماء. لا تشغّل `cloudflared tunnel login` من جديد، لأنه يستبدل ملف الشهادة المشترك.
2. **قواعد النفق** في `/etc/cloudflared/config.yml`، فوق القاعدة الأخيرة `- service: http_status:404`:

   ```yaml
     # --- Sura Academy (dev, D-32) ---
     - hostname: sura-api.masaros.net
       service: http://127.0.0.1:28000
     - hostname: sura.masaros.net
       service: http://127.0.0.1:28000
     - hostname: sura-app.masaros.net
       service: http://127.0.0.1:28081
     - hostname: sura-teacher.masaros.net
       service: http://127.0.0.1:28082
     - hostname: sura-files.masaros.net
       service: http://127.0.0.1:29000
     - hostname: sura-mail.masaros.net
       service: http://127.0.0.1:28025
   ```

   الخطوات، وآخرها في وقت هادئ لأن إعادة التشغيل تقطع كل مضيفات النفق ثوانيَ:

   ```bash
   sudo cp /etc/cloudflared/config.yml /etc/cloudflared/config.yml.bak.sura-$(date +%Y%m%d-%H%M%S)
   sudoedit /etc/cloudflared/config.yml
   cloudflared tunnel --config /etc/cloudflared/config.yml ingress validate
   cloudflared tunnel --config /etc/cloudflared/config.yml ingress rule https://sura-api.masaros.net/webhooks/test
   cloudflared tunnel --config /etc/cloudflared/config.yml ingress rule https://<an-existing-host>/
   sudo systemctl restart cloudflared
   ```

   الأمر الرابع يجب أن يطابق قاعدة سُرى، والخامس يجب أن يطابق قاعدة المضيف القائم كما كانت.
3. **Cloudflare Access** (Zero Trust ← Access ← Applications):
   - تطبيق Self-hosted واحد يضم `sura` و`sura-api` و`sura-app` و`sura-teacher` و`sura-mail`، بسياسة Allow لبريدك وللمختبِرين، مع تفعيل «Bypass OPTIONS requests to origin» في إعدادات CORS.
   - تطبيق للمسار `sura-api.masaros.net/webhooks/*` بسياسة Bypass لـ Everyone، لمزودي الدفع والفيديو.
   - تطبيق لـ `sura-files.masaros.net` بسياسة Bypass.
   - مرة لكل متصفح: افتح `https://sura-api.masaros.net/up` ليحصل على ملف Access لمضيف الـ API، ثم تنجح طلبات التطبيقات إليه.

## 8. ما لا يُفعل على هذا الخادم

- لا بيانات أسر أو أطفال حقيقية (D-32)، ولا مفاتيح إنتاج.
- لا `sudo` ولا `systemctl` ولا `crontab` ولا تعديل لنفق Cloudflare من جلسات Claude.
- لا `prune` لأي مورد في Docker، ولا أوامر docker على حاويات غير سُرى.
- لا `gh auth setup-git`، لأنه يغيّر إعداد git العام لكل المشاريع على الحساب.
- لا `npx playwright install --with-deps` ولا `playwright install chrome`، ولا حزم apt يقترحها `flutter doctor`.
- لا قراءة لـ `~/.cloudflared` أو `/etc/cloudflared` أو `~/.ssh` أو `~/.config/gh` أو ملفات `.env`.

## 9. الصيانة

- **إذا رُقّي PHP 8.4 في CloudPanel:** أعد بناء الامتدادات بالأمر في الملحق، ثم تحقق بـ `php -m` في بيئة سُرى.
- **Chrome:** `sudo apt-get install --only-upgrade google-chrome-stable` من حين لآخر، لأن التحديثات التلقائية على الخادم لا تشمله.
- **Node 26** يصبح LTS في 2026-10-28: `nvm install 26 --reinstall-packages-from=24`، ثم حدّث الروابط في `~/.local/share/sura/bin`.
- **Playwright:** عند تثبيت `@playwright/test` في المشروع استخدم 1.64.x، أو أعد `npx playwright install chromium` بالإصدار المثبت.
- **inotify:** قياس 2026-10-09 للمستخدم 1000: 13 من 128 instance و94 من 65,536 watch، والحد يضعه `/usr/lib/sysctl.d/30-tracker.conf`. إن ظهر خطأ ENOSPC في مراقبة الملفات فأضف `/etc/sysctl.d/99-zz-sura-inotify.conf` بالقيمة `fs.inotify.max_user_watches=262144`، وطبّقه وحده بـ `sudo sysctl -p <الملف>`.
- **`CONTEXT7_API_KEY`:** يضعه صاحب المشروع في `~/.config/sura/secrets.env` بصلاحية 600. حتى ذلك الحين يظهر تحذير context7 في `/mcp`.

## ملحق: إعادة بناء امتدادات PHP

يُنفَّذ من طرفيتك لا من جلسة Claude، لأن الحارس يمنع `docker run`. استخدم وسم `-bookworm` بإصدار PHP المطابق للخادم (`php8.4 -r 'echo PHP_VERSION;'`)، لأن وسم trixie يتطلب glibc أحدث من الخادم.

```bash
V=$(php8.4 -r 'echo PHP_VERSION;'); S=$HOME/.local/share/sura
docker run --rm --cpus=2 --memory=1g -e HOST_UID=$(id -u) -e HOST_GID=$(id -g) \
  -v "$S/php/ext:/out" "php:$V-cli-bookworm" bash -euo pipefail -c '
    apt-get update -qq && apt-get install -y -qq --no-install-recommends libpq-dev
    docker-php-ext-install -j2 pgsql pdo_pgsql && pecl install pcov-1.0.12
    d=$(php-config --extension-dir)
    install -m 0644 -o "$HOST_UID" -g "$HOST_GID" "$d/pgsql.so" "$d/pdo_pgsql.so" "$d/pcov.so" /out/'
docker image rm "php:$V-cli-bookworm"
```
