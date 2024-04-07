## 介绍


在这个项目中，尝试使用 Filament 小组件创建统计用户信息的卡片用来显示有关用户的统计信息。

并且通过添加 livewire 事件来刷新小组件的数据。

![](https://github.com/curder/filament-widget-refresh-demo/assets/8327004/59af78b5-b618-41b0-8fb7-31c80c07e54f)

## 安装

安装一个名为 `filament-widget-refresh-demo` 的项目：

```bash
laravel new filament-widget-refresh-demo
```

通过下面的命令安装 Filament:

```bash
cd filament-widget-refresh-demo
composer require filament/filament
php artisan filament:install --panels
```

编辑一下 `users` 迁移文件，添加 `is_admin` 和 `is_active` 字段：

```php
// database\migrations\2014_10_12_000000_create_users_table.php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->boolean('is_admin');
    $table->boolean('is_active');
    $table->rememberToken();
    $table->timestamps();
});
```

编辑工厂文件，添加对新增的字段的支持。

```php
// databse\factories\UserFactory.php
public function definition(): array
{
    return [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'email_verified_at' => now(),
        'password' => 'password',
        'is_admin' => fake()->boolean(),
        'is_active' => fake()->boolean(),
        'remember_token' => Str::random(10),
    ];
}
```

修改数据库填充文件 `DatabaseSeeder.php`。

```php
//database\seeders\DatabaseSeeder.php

use App\Models\User;
public function run()
{
    User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
    User::factory(100)->create();
}
```

编辑用户模型的 `fillable` 属性。

```php
// app\Models\User.php
protected $fillable = [
    'name',
    'email',
    'password',
    'is_active',
    'is_admin',
];
```

运行迁移命令，填充数据：

```bash
php artisan migrate --seed
```

## 用户资源

通过下面的命令可以快速创建用户资源管理和小组件：

```bash
php artisan make:filament-resource User --simple --generate
php artisan make:filament-widget UserOverview --resource=UserResource --stats-overview
```

在新建的小组件代码类中编写逻辑用于显示不同的统计数据：

```php
// app\Filament\Resources\UserResource\Widgets\UserOverview.php
<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserOverview extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $usersCount = User::query()->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN is_admin THEN 1 ELSE 0 END) AS admin,
            SUM(CASE WHEN is_active THEN 1 ELSE 0 END) AS active
        ')->first();

        return [
            Stat::make('Total', $usersCount->total)
                ->color('primary')
                ->description('Total users'),

            Stat::make('Admin', $usersCount->admin)
                ->color('danger')
                ->description('Admin users'),

            Stat::make('Active', $usersCount->active)
                ->color('success')
                ->description('Active users'),

        ];
    }
}
```

添加 `getHeaderWidgets()` 方法，并添加对小组件的引用：

```php
// app\Filament\Resources\UserResource\Pages\ManageUsers.php
protected function getHeaderWidgets(): array
{
    return [
        UserOverview::class,
    ];
}
```

当删除用户时，这些小部件不会更新，刷新页面时才能更新小组件的数据。

## 自动刷新小组件

利用 Livewire 事件监听器来完成当更新资源时自动刷新小组件。

### Livewire 事件监听器

Livewire 的事件监听器是一个键值对，其中键是需要监听的事件名，值是要在组件上调用的方法。

也可以使用 `$refresh` 魔术操作来重新渲染组件，而无需触发任何操作。

下面需要将用户的小组件 `OverviewWidget` 中添加事件监听器：

```php
// app\Filament\Resources\UserResource\Widgets\UserOverview.php

protected function getListeners(): array
{
    return [
        'refreshUserOverview' => '$refresh',
    ];
}
```

### `after()` 方法

```php
// app\Filament\Resources\UserResource\Pages\UserResource.php

Tables\Actions\DeleteAction::make()
    ->after(function (Pages\ManageUsers $livewire) {
        $livewire->dispatch('refreshUserOverview');
    }),
```

此刻再对用户数据删除时会自动更新小组件的数据。

![](https://github.com/curder/filament-widget-refresh-demo/assets/8327004/702ccbbd-7341-49a3-b94b-27a90ca4b290)