// UI strings for the Zoom web probe, Arabic first (the spike's equivalent of an l10n file).
window.SPIKE_STRINGS = {
  ar: {
    title: 'Zoom في المتصفح (تجربة)',
    intro: 'صفحة قياس لـ Zoom Video SDK للويب. تقيس زمن الدخول حتى ظهور الفيديو بالطريقة نفسها التي يقيس بها التطبيق.',
    role: 'الدور', roleStudent: 'طالب', roleTeacher: 'معلمة', seat: 'رقم المقعد', room: 'اسم الغرفة',
    device: 'اسم الجهاز (مثل ipad-safari)', code: 'رمز التجربة', join: 'دخول', trials: '20 محاولة تلقائية', leave: 'خروج',
    muteAll: 'كتم الجميع', recordStart: 'بدء التسجيل', recordStop: 'إيقاف التسجيل',
    joining: 'جارٍ الدخول…', waiting: 'متصل، بانتظار فيديو مشارك آخر…',
    joined: (s) => `زمن الدخول حتى ظهور الفيديو: ${s} ث`,
    trial: (n, total) => `المحاولة ${n} من ${total}`,
    done: (ok, total, p50, p95) => `انتهت المحاولات (${ok} ناجحة من ${total}). الوسيط ${p50} ث، و95% تحت ${p95} ث`,
    error: (m) => `خطأ: ${m}`,
    trialLine: (s, token, connected) => `${s} ث · رمز الدخول ${token} مث · الاتصال ${connected} مث`,
  },
};
