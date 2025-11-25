# 🗺️ نظام Geofencing - دليل كامل

## ✨ الميزات المضافة

### 1. **نظام Geofencing كامل**
- إنشاء مناطق جغرافية (Polygon أو Circle)
- ربط الأجهزة بالمناطق الجغرافية
- مراقبة real-time للأجهزة
- إنذارات تلقائية عند الدخول/الخروج

### 2. **أنواع Geofences**
- **Polygon**: مناطق حرة الشكل بأي عدد من النقاط
- **Circle**: دوائر بنصف قطر محدد بالمتر

### 3. **وضعيات المراقبة (Modes)**
- **Inside Mode**: إنذار عندما يخرج الجهاز من المنطقة
- **Outside Mode**: إنذار عندما يدخل الجهاز المنطقة

### 4. **Real-time Monitoring**
- فحص تلقائي مع كل موقع جديد
- إنشاء Alarm Notifications فوراً
- منع تكرار الإنذارات (5 دقائق buffer)

## 📁 الملفات المضافة

### Database:
```
database/migrations/2025_11_19_000000_create_geofence.php
```

### Models:
```
app/Domains/Geofence/Model/Geofence.php
app/Domains/Geofence/Model/DeviceGeofence.php
```

### Services:
```
app/Domains/Geofence/Service/GeofenceChecker.php
```

### Controllers:
```
app/Domains/Geofence/Controller/Index.php
app/Domains/Geofence/Controller/Create.php
app/Domains/Geofence/Controller/Store.php
app/Domains/Geofence/Controller/Update.php
app/Domains/Geofence/Controller/Attach.php
app/Domains/Geofence/Controller/Detach.php
app/Domains/Geofence/Controller/router.php
```

### Views:
```
resources/views/domains/geofence/index.blade.php
```

### Modified Files:
```
app/Domains/Device/Model/Device.php (+ geofences relationship)
app/Domains/Position/Action/Create.php (+ geofence checking)
resources/views/domains/device/update-layout.blade.php (+ Geofencing tab)
```

## 🗄️ قاعدة البيانات

### جدول `geofence`:
```sql
- id
- name (string)
- type ('polygon' | 'circle')
- description (text, nullable)
- geom (polygon geometry, nullable)
- center (point geometry, nullable for circle)
- radius (decimal, nullable for circle)
- color (string, default '#FF0000')
- enabled (boolean, default true)
- user_id (foreign key)
- timestamps
```

### جدول `device_geofence` (Pivot):
```sql
- id
- device_id (foreign key)
- geofence_id (foreign key)
- mode ('inside' | 'outside')
- enabled (boolean, default true)
- timestamps
- unique(device_id, geofence_id)
```

### تحديث `alarm_notification`:
```sql
+ geofence_id (foreign key, nullable)
```

## 🚀 كيفية الاستخدام

### 1. تشغيل Migration:
```bash
php artisan migrate
```

### 2. إنشاء Geofence جديد:
```
1. اذهب إلى Device > Geofencing Tab
2. اضغط "Create New Geofence"
3. ارسم المنطقة على الخريطة
4. حدد الاسم واللون
```

### 3. ربط Geofence بالجهاز:
```
1. في صفحة Geofencing للجهاز
2. اضغط "Attach" على الـ Geofence المطلوب
3. اختر Mode:
   - "Alarm when OUTSIDE": إنذار عند الخروج (للمناطق المسموح بها)
   - "Alarm when INSIDE": إنذار عند الدخول (للمناطق الممنوعة)
4. فعّل المراقبة
```

### 4. مراقبة الإنذارات:
```
- الإنذارات تظهر تلقائياً في Dashboard
- نوع الإنذار: "geofence"
- تفاصيل الإنذار تتضمن:
  - اسم المنطقة
  - نوع الانتهاك (entered/exited)
  - الموقع الجغرافي
  - الوقت
```

## 🔧 API Methods

### Geofence Model:
```php
// Check if point is inside geofence
$geofence->containsPoint($latitude, $longitude): bool

// Get GeoJSON representation
$geofence->geojson: array

// Get center coordinates (for circles)
$geofence->center_latitude: float
$geofence->center_longitude: float
```

### GeofenceChecker Service:
```php
use App\Domains\Geofence\Service\GeofenceChecker;

$checker = new GeofenceChecker();

// Check position and create alarms if needed
$alarms = $checker->checkPosition($position);

// Check device location in real-time
$status = $checker->checkDeviceLocation($device, $lat, $lng);
```

### Device Model:
```php
// Get all geofences for device
$device->geofences()->get();

// Attach geofence to device
$device->geofences()->attach($geofence_id, [
    'mode' => 'inside', // or 'outside'
    'enabled' => true
]);

// Detach geofence
$device->geofences()->detach($geofence_id);
```

## 📊 أمثلة الاستخدام

### مثال 1: مراقبة منطقة عمل (Inside Mode)
```
سيناريو: مراقبة أن السائق في منطقة العمل
- Type: Polygon يغطي منطقة العمل
- Mode: "Alarm when OUTSIDE"
- النتيجة: إنذار فوري عند خروج السائق من المنطقة
```

### مثال 2: منع دخول منطقة خطرة (Outside Mode)
```
سيناريو: منع دخول السائق لمنطقة غير آمنة
- Type: Circle حول المنطقة الخطرة
- Mode: "Alarm when INSIDE"
- النتيجة: إنذار فوري عند دخول السائق المنطقة
```

### مثال 3: مراقبة عدة مناطق لنفس الجهاز
```
يمكن ربط أكثر من geofence لنفس الجهاز:
- Geofence 1: منطقة العمل (inside mode)
- Geofence 2: منطقة ممنوعة (outside mode)
- Geofence 3: منطقة عملاء (inside mode)
```

## 🎨 الواجهة

### Tab جديد في Device:
- يظهر في صفحة تعديل الجهاز
- عنوان: "Geofencing"
- يحتوي على:
  - قائمة جميع Geofences
  - حالة الربط لكل منطقة
  - زر Attach/Detach
  - خريطة تفاعلية

### الخريطة التفاعلية:
- عرض جميع Geofences
- ألوان مختلفة حسب الحالة:
  - المناطق المربوطة: لون داكن وملء 30%
  - المناطق غير المربوطة: لون فاتح وملء 10%
- Popup عند الضغط على المنطقة

## 🔔 نظام الإنذارات

### AlarmNotification يحتوي على:
```php
[
    'type' => 'geofence',
    'name' => 'Geofence Entered: اسم المنطقة' // or 'Geofence Exited'
    'config' => [
        'geofence_name' => 'اسم المنطقة',
        'geofence_mode' => 'inside', // or 'outside'
        'violation_type' => 'entered', // or 'exited'
        'latitude' => 24.7136,
        'longitude' => 46.6753,
    ],
    'geofence_id' => 1,
    'position_id' => 123,
    'vehicle_id' => 5,
    'dashboard' => true,
    'telegram' => true,
]
```

### منع التكرار:
- لا يتم إنشاء إنذار جديد إذا كان هناك إنذار مفتوح في آخر 5 دقائق
- يمنع spam الإنذارات عند الحركة على الحدود

## 🔍 Real-time Checking

### التحقق التلقائي:
```php
// في Position Create Action:
protected function jobGeofence(): void
{
    $checker = new GeofenceChecker();
    $checker->checkPosition($this->row);
}
```

### الفحص يتم:
1. عند كل موقع جديد من الجهاز
2. بشكل متزامن (synchronous) للحصول على سرعة
3. مع error handling كامل
4. Logging لكل عملية

## 📈 Performance

### تحسينات الأداء:
- استخدام ST_Contains و ST_Distance_Sphere (MySQL spatial functions)
- Indexed columns (user_id, enabled)
- Lazy loading للعلاقات
- Buffer للإنذارات (5 دقائق)

### متطلبات:
- MySQL 5.7+ أو MariaDB 10.2+
- Spatial Extensions enabled (موجودة افتراضياً)
- Geometry columns with SRID 4326

## 🐛 استكشاف الأخطاء

### مشكلة: الإنذارات لا تظهر
```
1. تأكد من:
   - Geofence enabled = true
   - Device-Geofence pivot enabled = true
   - Position تم حفظها بنجاح
2. شيك الـ logs:
   - storage/logs/laravel.log
   - ابحث عن "Geofence"
```

### مشكلة: Geofence لا يظهر على الخريطة
```
1. تأكد من:
   - geom أو (center + radius) محفوظ صح
   - اللون color محدد
2. شيك الـ GeoJSON:
   - $geofence->geojson
   - يجب يرجع array
```

### مشكلة: الفحص بطيء
```
1. أضف Indexes:
   CREATE INDEX idx_geofence_enabled ON geofence(enabled);
   CREATE INDEX idx_device_geofence_enabled ON device_geofence(enabled);
2. استخدم Queue للفحص (optional):
   - حوّل jobGeofence() لـ dispatch job
```

## 🔐 Security

### التحقق من الصلاحيات:
- جميع Routes محمية بـ 'user-auth' middleware
- Device ownership check في جميع Controllers
- Geofence ownership check قبل الربط/الفك

## 🌐 الترجمة

### إضافة ترجمات:
```php
// في resources/lang/ar/...
'Geofencing' => 'التسييج الجغرافي',
'Create New Geofence' => 'إنشاء منطقة جغرافية جديدة',
'Alarm when OUTSIDE' => 'إنذار عند الخروج',
'Alarm when INSIDE' => 'إنذار عند الدخول',
```

---

**تم بنجاح! 🎉**
النظام الآن جاهز لمراقبة الأجهزة في real-time مع إنذارات تلقائية!
