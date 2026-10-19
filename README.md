# Капча «Пример на сложение» + маска телефона

Простая защита форм от роботов без сторонних сервисов (Google reCAPTCHA, Яндекс SmartCaptcha и т. п.).

Посетитель видит картинку с примером `3 + 5 = ?` и вписывает ответ. Правильный ответ хранится на сервере в сессии, в коде страницы его нет. Дополнительно поле телефона получает маску `+7(999)999-99-99`, а сервер проверяет, что номер введён полностью.

---

## Состав

```
captcha/                  ← эту папку копируете на свой сайт
├── captcha.php           — рисует картинку с примером и запоминает ответ в сессии
├── captcha-check.php     — функции проверки: captcha_is_valid() и captcha_phone_is_valid()
├── captcha.js            — загружает и обновляет картинку (при открытии окна, по клику)
├── tel-mask.js           — включает маску +7(999)999-99-99 на полях с классом telMask
└── inputmask.min.js      — библиотека масок Inputmask 5.0.6 (MIT, github.com/RobinHerbots/Inputmask)

example/                  ← рабочий пример, на сайт копировать не обязательно
├── index.php             — страница с формой
└── send.php              — обработчик формы
```

## Как это работает

1. На странице есть картинка `<img class="captchaImg">` и поле `<input name="captcha">`.
2. `captcha.js` подставляет в картинку адрес `captcha.php?<время>`. Браузер загружает картинку.
3. `captcha.php` придумывает пример, записывает ответ в `$_SESSION['captcha']` и отдаёт PNG.
4. Посетитель отправляет форму. Обработчик формы вызывает `captcha_is_valid($_POST['captcha'])` — функция сравнивает ответ с сессией и сразу удаляет ответ (он одноразовый).
5. Обработчик вызывает `captcha_phone_is_valid($_POST['tel'])` — проверка, что телефон имеет вид `+7(999)999-99-99`.
6. Обе проверки прошли — отправляем заявку. Нет — возвращаем посетителя к форме с ошибкой.

## Быстрая проверка

1. Скопируйте весь репозиторий в папку сайта на локальном сервере
2. Откройте index.php в папке example.
3. Должна появиться картинка с примером. Клик по ней — новый пример.
4. Отправьте форму с правильным и неправильным ответом, с полным и неполным телефоном — увидите разные сообщения.

---

## Установка на сайт

### Шаг 1. Скопируйте папку `captcha`

Положите папку `captcha/` в любое место сайта, доступное из браузера. Например, в корень.

Дальше в примерах считается, что папка доступна по адресу `/captcha/`.

### Шаг 2. Добавьте в форму картинку и поле ответа

Внутрь `<form>` (рядом с кнопкой отправки) вставьте:

```html
<!-- Капча «Пример на сложение» -->
<img class="captchaImg" alt="Пример" width="120" height="40" />
<input type="text" name="captcha" inputmode="numeric" maxlength="2" autocomplete="off" placeholder="Ответ на пример*" required />
```

Обязательно:

- у картинки — класс `captchaImg` (атрибут `src` указывать **не нужно**, его подставит `captcha.js`);
- у поля — имя `name="captcha"`;
- картинка и поле должны быть внутри одной `<form>`.

### Шаг 3. Включите маску на поле телефона

Добавьте полю телефона класс `telMask` и имя `tel`:

```html
<input type="text" name="tel" class="telMask" placeholder="Ваш телефон*" required />
```

### Шаг 4. Подключите скрипты

Перед закрывающим тегом `</body>`:

```html
<script src="/captcha/inputmask.min.js"></script>
<!-- обязательно ДО tel-mask.js -->
<script src="/captcha/tel-mask.js"></script>
<script src="/captcha/captcha.js"></script>
```

`captcha.js` сам найдёт `captcha.php` в своей папке. Если `captcha.php` лежит в другом месте, укажите адрес у картинки: `<img class="captchaImg" data-captcha-src="/другой/путь/captcha.php">`.

### Шаг 5. Добавьте проверку в обработчик формы

В PHP-файле, куда отправляется форма (атрибут `action` формы), **в самом начале**, до любого вывода HTML:

```php
<?php
// Подключаем функции проверки (путь — от этого файла до папки captcha)
require_once __DIR__ . "/captcha/captcha-check.php";

// Куда вернуть посетителя после отправки
$back = !empty($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "/";

// Капча
if (!captcha_is_valid(isset($_POST["captcha"]) ? $_POST["captcha"] : null)) {
	$_SESSION["captcha_message"] = "Неверный ответ на пример. Попробуйте ещё раз.";
	header("Location: " . $back);
	exit();
}

// Телефон
if (!captcha_phone_is_valid(isset($_POST["tel"]) ? $_POST["tel"] : null)) {
	$_SESSION["captcha_message"] = "Введите телефон полностью.";
	header("Location: " . $back);
	exit();
}

// ...дальше ваш код отправки заявки (mail(), Telegram и т. д.)
```

Готовый полный обработчик — [example/send.php](example/send.php).

### Шаг 6. (По желанию) Покажите посетителю сообщение

Обработчик кладёт текст ошибки или «спасибо» в `$_SESSION['captcha_message']`. Чтобы показать его на странице с формой:

```php
<?php session_start();
/* в самом начале страницы, до <!DOCTYPE> */
?>
...
<?php if (!empty($_SESSION["captcha_message"])): ?>
	<div class="message"><?php echo htmlspecialchars($_SESSION["captcha_message"], ENT_QUOTES, "UTF-8"); ?></div>
	<?php unset($_SESSION["captcha_message"]); ?>
<?php endif; ?>
```

Пример — [example/index.php](example/index.php).

---

## Форма во всплывающем окне (Bootstrap modal)

Ничего дополнительно делать не нужно. Если картинка `captchaImg` находится внутри элемента с классом `.modal`, `captcha.js` загружает новый пример **при каждом открытии окна**, а не при загрузке страницы.

Это важно: правильным считается ответ только на **последний показанный** пример. Если на странице несколько окон с формами, загрузка картинок сразу во всех окнах «сломала» бы все, кроме последнего.

```html
<div class="modal fade" id="callbackModal" tabindex="-1">
	<div class="modal-dialog">
		<form method="post" action="/send.php" class="modal-content">
			<div class="modal-body">
				<input type="text" name="tel" class="form-control telMask" placeholder="Ваш телефон*" required />
				<div class="d-flex align-items-center gap-2 mt-3">
					<img class="captchaImg" alt="Пример" width="120" height="40" />
					<input type="text" name="captcha" class="form-control" inputmode="numeric" maxlength="2" autocomplete="off" placeholder="Ответ на пример*" required />
				</div>
			</div>
			<div class="modal-footer">
				<button type="submit" class="btn btn-primary">Перезвонить</button>
			</div>
		</form>
	</div>
</div>
```

## Установка в тему WordPress

1. Скопируйте папку `captcha/` в папку темы: `wp-content/themes/ваша-тема/captcha/`.
2. Разметку формы добавьте как в шаге 2 и 3.
3. Скрипты подключите через адрес темы (в шаблоне перед `</body>` или в `footer.php`):
    ```php
    <script src="<?php echo get_template_directory_uri(); ?>/captcha/inputmask.min.js"></script>
    <script src="<?php echo get_template_directory_uri(); ?>/captcha/tel-mask.js"></script>
    <script src="<?php echo get_template_directory_uri(); ?>/captcha/captcha.js"></script>
    ```
    Или через `functions.php`:
    ```php
    add_action("wp_enqueue_scripts", function () {
    	$dir = get_template_directory_uri() . "/captcha/";
    	wp_enqueue_script("inputmask", $dir . "inputmask.min.js", [], null, true);
    	wp_enqueue_script("tel-mask", $dir . "tel-mask.js", ["inputmask"], null, true);
    	wp_enqueue_script("captcha", $dir . "captcha.js", [], null, true);
    });
    ```
4. Если обработчик формы — отдельный PHP-файл в теме (например, `mails/send.php`), подключайте проверку от его папки:
    ```php
    require_once dirname(__DIR__) . "/captcha/captcha-check.php";
    ```
5. Для показа сообщения (шаг 6) вызов `session_start();` поставьте в самое начало шаблона (`header.php` или `index.php`), до `<!DOCTYPE html>`.

---

## Настройка

| Что изменить                 | Где                                                                                              |
| ---------------------------- | ------------------------------------------------------------------------------------------------ |
| Размер картинки              | `$width`, `$height` в `captcha.php` + атрибуты `width`/`height` у `<img>`                        |
| Цвета, количество линий-шума | `captcha.php`, блоки `imagecolorallocate` и цикл `for`                                           |
| Ключ ответа в сессии         | `$captcha_session_key` в `captcha.php` **и** `$key` в `captcha-check.php` (одинаковые)           |
| Маска телефона               | `tel-mask.js` **и** шаблон в `captcha_phone_is_valid()` в `captcha-check.php` (должны совпадать) |

Если меняете диапазон чисел в примере (сейчас 1–9), проверьте шаблон `\d{1,2}` в `captcha_is_valid()` и `maxlength` у поля ответа.
