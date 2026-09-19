# مهدا — جستجوی هوشمند v29

## بررسی معماری قبل از تغییر

- Search عمومی فعلی در `app/Modules/Content/api.php` با action `global_search` و UI در `templates/pages/search/index.php` + `assets/search-v9.js` قرار داشت.
- Repository اصلی محتوا `app/Modules/Content/ContentRepository.php` است و Search عادی دست‌نخورده باقی مانده است.
- وضعیت تأیید واقعی محتوا در پروژه `review_status='approved'` است. AI Search سخت‌گیرانه‌تر از Search عمومی فقط `visibility='showcase'` + `review_status='approved'` + `archived_at IS NULL` را می‌پذیرد؛ بنابراین Draft/Pending/Rejected/Hidden وارد نتیجه هوشمند نمی‌شوند.
- Categoryها در `mahda_categories`، مناسبت‌ها در `mahda_occasions`، اهداف در `mahda_goals` و ارتباط‌های محتوا در `mahda_content_*` ذخیره می‌شوند.
- گروه‌های سنی فعلی محتوا `age_4_6` و `age_7_10` هستند.
- Active Profile از `$_SESSION['profile_id']`/`mahda_current_user()` تشخیص داده می‌شود و Preference سنی از `profile_data_json.ages` خوانده می‌شود؛ سن صریح Query اولویت دارد.
- Redis abstraction موجود `Mahda\Core\Redis\RedisClient` است و برای Cache تحلیل، Circuit Breaker و کلید سهمیه reuse شده است. محدودیت نهایی هم‌زمانی در DB با Unique Key اتمیک است تا قطع Redis باعث دور زدن سهمیه نشود.
- API v1 از `api/v1/index.php` به actionهای Kernel نگاشت می‌شود.
- کارت محتوای Search موجود حفظ شده و برای نتایج هوشمند همان زبان بصری با Action ذخیره/پسند اضافه شده است.

## فایل‌های اصلی اضافه‌شده

- `config/ai.php`
- `app/Core/Environment.php`
- `app/Core/Ai/AiProviderInterface.php`
- `app/Core/Ai/OpenAiCompatibleProvider.php`
- `app/Core/Ai/AiProviderFactory.php`
- `app/Modules/AiSearch/*`
- `assets/search-v10.js`
- `tools/verify-ai-search.php`
- `.mahda-ai.env` به‌عنوان Secret استقرار (در `.gitignore` و با دسترسی وب مسدود)

## Migration

Migration شماره 29 جدول `mahda_ai_search_usages` را ایجاد می‌کند. بعد از Replace یک بار اجرا شود:

```bash
php tools/migrate.php
```

این جدول Provider/Model، hash و متن Query، Tokenها، latency، status، result count و error code را نگه می‌دارد. Secret یا Authorization Header ثبت نمی‌شود.

## Routeهای جدید

- `POST /api/v1/search/ai`
- `GET /api/v1/search/ai/quota`

Search معمولی همچنان `GET /api/v1/search` و action قدیمی `global_search` را دارد و هیچ AI Call در تایپ/ debounce آن رخ نمی‌دهد.

## رفتار محصول

- Guest فقط Search معمولی دارد و برای AI پیام ورود می‌بیند.
- Queryهای خیلی کلی مانند «محرم» یا «بازی» قبل از AI با Chipهای داخلی مهدا clarification می‌گیرند و سهمیه مصرف نمی‌شود.
- AI فقط Query را به JSON ساختاریافته تبدیل می‌کند؛ هیچ SQL یا محتوای آزاد از AI اجرا/نمایش داده نمی‌شود.
- category/occasion/goal خروجی AI با Taxonomy واقعی whitelist می‌شوند.
- Ranking در PHP انجام می‌شود؛ LLM برای Ranking فراخوانی نمی‌شود.
- نتیجه حداکثر 6 محتواست.
- اگر Exact Match نباشد Nearest Match نشان داده می‌شود، ولی Category/Occasion اصلی تا جای ممکن حفظ می‌شوند.
- Profile age فقط Preference است و Query صریح آن را override می‌کند.
- Analysis عمومی Query در Redis با Taxonomy hash cache می‌شود و داده Profile وارد Cache عمومی نمی‌شود.
- Circuit Breaker Redis-based است و خطای Provider Search عادی را خراب نمی‌کند.

## Config / Kill Switch

Config از Environment/Secret خوانده می‌شود. مهم‌ترین متغیرها:

- `AI_SEARCH_ENABLED`
- `AI_PROVIDER`
- `AI_MODEL`
- `AI_MODEL_FALLBACK`
- `AI_BASE_URL`
- `AI_API_KEY`
- `AI_SEARCH_DAILY_LIMIT`
- `AI_SEARCH_MAX_RESULTS`
- `AI_SEARCH_MAX_OUTPUT_TOKENS`
- `AI_SEARCH_TIMEOUT_SECONDS`

برای خاموش‌کردن بدون تغییر کد: `AI_SEARCH_ENABLED=false`.

## Secret

به درخواست صاحب پروژه، کلید استقرار داخل فایل Secret `.mahda-ai.env` قرار گرفته و از PHP/JS/HTML جداست. `.htaccess` دسترسی مستقیم وب را می‌بندد و `.gitignore` مانع Commit تصادفی می‌شود. اگر سرور Environment واقعی دارد، بهتر است Secret فایل حذف و متغیرها مستقیماً در Environment ست شوند.

## Usage

فعلاً Usage از جدول `mahda_ai_search_usages` قابل مشاهده است. نمونه:

```sql
SELECT user_id,provider,model,input_tokens,output_tokens,total_tokens,latency_ms,result_count,status,error_code,created_at
FROM mahda_ai_search_usages
ORDER BY id DESC
LIMIT 100;
```

## Token تقریبی

با Taxonomy فعلی، هر Query معمولاً حدود 900 تا 1800 input token و حداکثر 250 output token بودجه دارد. عدد واقعی از پاسخ Provider در Usage ذخیره می‌شود. این تخمین با افزایش Taxonomy تغییر می‌کند.

## Technical debt باقی‌مانده

- Integration Test واقعی Provider فقط روی سرور دارای DNS/Internet قابل انجام است؛ محیط ساخت این ZIP دسترسی DNS به Provider نداشت.
- Semantic/Vector Search عمداً در این فاز اضافه نشده است.
- UI مدیریتی برای Analytics AI به فاز مدیریت موکول شده؛ داده خام Usage از الان ثبت می‌شود.
