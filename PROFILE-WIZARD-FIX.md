# این سند منسوخ شده است

این سند مربوط به بازطراحی قبلی ویترین بود. در نسخه فعلی، بنا به درخواست محصول، **ویترین به طراحی قبلی بازگردانده شده و فقط آواتار کاملاً دایره‌ای شده است**. برای وضعیت فعلی به `UPLOAD-PREVIEW-PROFILE-FIX.md` مراجعه کنید.

# اصلاح پروفایل و ثبت محتوا

این نسخه روی `mahda-login-fixed.zip` ساخته شده است.

## پروفایل / ویترین

- هدر پروفایل از حالت خالی و فشرده خارج شد و به یک Hero اختصاصی تبدیل شد.
- آواتار روی Hero قرار گرفت و نام، نقش، شهر، بیو، آمار و دکمه ویرایش/دنبال‌کردن بازچینی شدند.
- تب‌های «محتواها» و «درباره ...» تمیزتر شدند.
- گرید محتوا در موبایل دو ستونه شد تا کارت‌ها بسیار کوچک نشوند.
- محتوایی که کاور ندارد دیگر به شکل یک مستطیل خالی نمایش داده نمی‌شود و Placeholder برندشده دارد.
- Empty state برای ویترین خالی اضافه شد.

## ثبت محتوا

فرآیند از ۷ گام UI به ۹ گام تبدیل شد. پنج گام اول دست‌نخورده‌اند و چهار گام پایانی به شکل زیر هستند:

6. کاور
7. توضیح محتوا: ویدیو / عکس / متن
8. فایل همراه
9. پیش‌نمایش و انتشار

در گام‌های پایانی Stepper چهارتایی جدا نمایش داده می‌شود تا روی موبایل شلوغ نشود.

### رسانه

- ویدیو و عکسِ محتوای اصلی با purpose موجود `main_file` ذخیره می‌شوند تا مدل فایل پیچیده نشود.
- `executionMethod` اکنون مقدار `image` را نیز می‌پذیرد، ضمن اینکه `audio` برای سازگاری محتوای قدیمی حفظ شده است.
- ویدیو همچنان از pipeline فعلی مهدا استفاده می‌کند: حداکثر ۵ دقیقه و خروجی نهایی زیر ۲۰MB.
- تصاویر از pipeline بهینه‌سازی فعلی عبور می‌کنند و هدف خروجی حدود ۲۰۰KB باقی می‌ماند.

## سازگاری Draft / Offline

- `current_step` از ۷ به ۹ گسترش یافت؛ نوع ستون `TINYINT` از قبل ظرفیت کافی دارد و migration دیتابیس جدید لازم نیست.
- Offline Draft Sync نیز stepهای ۱ تا ۹ را قبول می‌کند.
- Draftهای قدیمی حذف یا بازنویسی نمی‌شوند.

## فایل‌های اصلی تغییرکرده

- `app/Modules/Showcase/views.php`
- `templates/pages/showcase/index.php`
- `templates/pages/content/new.php`
- `assets/content-wizard-v14.css`
- `assets/content-wizard-v15.js`
- `app/Modules/Content/registration-contract.php`
- `app/Modules/Content/workflow-api.php`
- `app/Modules/OfflineSync/DraftSyncService.php`
- `app/Modules/Studio/page.php` (فقط سازگاری مقدار image، بدون بازطراحی مدیریت)

## تست

```bash
php tools/verify-release.php
php tools/verify-login.php
php tools/verify-phase27-28.php
php tools/verify-profile-wizard.php
node --check assets/content-wizard-v15.js
```

## Replace روی هاست

این بسته به‌صورت ZIP کامل جایگزین آماده شده است. نسخه فایل‌های CSS/JS در HTML cache-bust شده تا بعد از Replace ظاهر قدیمی از کش مرورگر باقی نماند.
