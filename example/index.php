<?php
// =============================================================================
// ПРИМЕР: СТРАНИЦА С ФОРМОЙ, КАПЧЕЙ И МАСКОЙ ТЕЛЕФОНА
// =============================================================================
//
// Открывается в браузере по адресу вида http://ваш-сайт/example/
// Форма отправляется в send.php, который проверяет капчу и телефон,
// запоминает результат в сессии и возвращает посетителя сюда.
// Здесь результат (сообщение) показывается над формой.
// =============================================================================

// Включаем сессию: из неё читаем сообщение о результате отправки формы.
// session_start() должен стоять до любого вывода HTML.
session_start();

// Сообщение о результате (его записал send.php) или пустая строка, если его нет
$message = isset( $_SESSION['captcha_message'] ) ? $_SESSION['captcha_message'] : '';

// Удаляем сообщение из сессии, чтобы оно не появлялось снова при обновлении страницы
unset( $_SESSION['captcha_message'] );
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<!-- Кодировка страницы -->
	<meta charset="utf-8">
	<!-- Правильный масштаб на телефонах -->
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- Заголовок вкладки браузера -->
	<title>Пример капчи</title>
	<!-- Простые стили только для примера; на вашем сайте они не нужны -->
	<style>
		/* Ограничиваем ширину страницы и задаём шрифт */
		body { font-family: sans-serif; max-width: 420px; margin: 40px auto; padding: 0 16px; }
		/* Поля формы — на всю ширину, с отступом снизу */
		input { display: block; width: 100%; box-sizing: border-box; padding: 8px; margin-bottom: 12px; font-size: 16px; }
		/* Строка «картинка + поле ответа»: картинка слева, поле справа */
		.captcha-row { display: flex; gap: 8px; align-items: center; margin-bottom: 12px; }
		/* Поле ответа в этой строке — без нижнего отступа */
		.captcha-row input { margin-bottom: 0; }
		/* Блок с сообщением о результате отправки */
		.message { padding: 12px; background: #f0f0f0; margin-bottom: 16px; }
	</style>
</head>
<body>

	<h1>Обратный звонок</h1>

	<?php if ( $message !== '' ) : ?>
		<!-- Сообщение о результате отправки. htmlspecialchars() защищает от
		     вставки чужого HTML-кода в страницу. -->
		<div class="message"><?php echo htmlspecialchars( $message, ENT_QUOTES, 'UTF-8' ); ?></div>
	<?php endif; ?>

	<!-- Форма отправляется методом POST в обработчик send.php -->
	<form method="post" action="send.php">

		<!-- Имя — необязательное поле -->
		<input type="text" name="name" placeholder="Ваше имя">

		<!-- Телефон. Класс telMask включает маску +7(999)999-99-99 (tel-mask.js).
		     Имя поля tel — его проверяет captcha_phone_is_valid() в send.php. -->
		<input type="text" name="tel" class="telMask" placeholder="Ваш телефон*" required>

		<!-- КАПЧА «Пример на сложение» -->
		<div class="captcha-row">
			<!-- Картинка с примером. Класс captchaImg обязателен: по нему её находит
			     captcha.js и сам подставляет адрес картинки (атрибут src не нужен).
			     Клик по картинке — новый пример. -->
			<img class="captchaImg" alt="Пример" width="120" height="40">
			<!-- Поле ответа. Имя captcha обязательно: его очищает captcha.js
			     и проверяет captcha_is_valid() в send.php.
			     inputmode="numeric" — на телефоне откроется цифровая клавиатура;
			     maxlength="2" — ответ не длиннее двух цифр (максимум 9 + 9 = 18);
			     autocomplete="off" — браузер не подставит старый ответ. -->
			<input type="text" name="captcha" inputmode="numeric" maxlength="2" autocomplete="off" placeholder="Ответ на пример*" required>
		</div>

		<!-- Кнопка отправки формы -->
		<button type="submit">Перезвонить</button>
	</form>

	<!-- Библиотека масок ввода — обязательно ДО tel-mask.js -->
	<script src="../captcha/inputmask.min.js"></script>
	<!-- Маска телефона для полей с классом telMask -->
	<script src="../captcha/tel-mask.js"></script>
	<!-- Загрузка и обновление картинки капчи. captcha.php ищется в той же папке. -->
	<script src="../captcha/captcha.js"></script>
</body>
</html>
