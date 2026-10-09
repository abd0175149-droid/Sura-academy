<?php
declare(strict_types=1);
/**
 * Re-split an updated Markdown export of the spec into per-domain files for review.
 * Usage: php scripts/split-spec.php docs/01-product/source/spec-latest.md
 * Output goes to docs/01-product/spec-incoming/ (the human reviews the diff and moves files into spec/).
 */
$src = $argv[1] ?? null;
if (!$src || !is_file($src)) {
    fwrite(STDERR, "usage: php scripts/split-spec.php <markdown export>\n");
    exit(1);
}
$map = [
    'الأولويات والقرارات التي تقوم عليها المواصفات' => '00-priorities-and-baseline-decisions',
    'مكونات المنصة والأدوار والصلاحيات' => '01-components-roles-rbac',
    'الموقع العام والتسجيل والتهيئة' => '02-public-site-and-onboarding',
    'بوابة ولي الأمر' => '03-parent-portal',
    'بوابة الطالب وحساب الطفل المُدار' => '04-student-portal',
    'الفصل الافتراضي وأدوات الحصة المباشرة' => '05-live-classroom',
    'بوابة المعلمة وعمليات المعلمات والمستحقات' => '06-teacher-portal-ops-compensation',
    'المحتوى والمنهج والتقييم والواجبات والتقارير' => '07-content-assessment-homework-reports',
    'الجدولة والجدول المنشور والمناطق الزمنية' => '08-scheduling-and-timezones',
    'الحضور ودفتر الرصيد ومحرك القواعد' => '09-attendance-ledger-rules',
    'الاشتراكات والتسعير والمدفوعات والفوترة' => '10-billing-and-payments',
    'الإشعارات والتواصل والدعم' => '11-notifications-messaging-support',
    'لوحة الإدارة والتحليلات والمؤشرات' => '12-admin-analytics-kpis',
    'البنية التقنية والتقنيات المقترحة' => '13-architecture-original',
    'نموذج البيانات وحالات الكيانات' => '14-data-model-and-states',
    'واجهات API والـ webhooks والمهام الخلفية' => '15-api-webhooks-jobs',
    'الأدوات والخدمات الخارجية' => '16-external-services',
    'الأمن والخصوصية وحماية الطفل' => '17-security-privacy-safeguarding',
    'المتطلبات غير الوظيفية' => '18-non-functional-requirements',
    'البنية التحتية وDevOps والاختبار وضمان الجودة' => '19-infrastructure-devops-qa',
    'خطة الإصدارات' => '20-release-plan',
    'الملاحق' => '21-appendices',
];
$root = dirname(__DIR__);
$out = $root . '/docs/01-product/spec-incoming';
@mkdir($out, 0775, true);
$parts = preg_split('/^## /m', (string) file_get_contents($src));
array_shift($parts);
$written = 0;
foreach ($parts as $part) {
    $title = trim(strtok($part, "\n"));
    if (!isset($map[$title])) {
        fwrite(STDERR, "⚠ unknown section: {$title} (add it to the map or a new spec file)\n");
        continue;
    }
    file_put_contents("{$out}/{$map[$title]}.md", '## ' . rtrim($part) . "\n");
    $written++;
}
echo "wrote {$written} files to docs/01-product/spec-incoming/ — review the diff against docs/01-product/spec/ before replacing.\n";
