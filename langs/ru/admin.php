<?php

// Admin cabinet

$lang['UCAB_ADMIN_IPB_CONV']='IPB Data Conversion Tool';

$lang['ADMCAB_VISSTAT_USERS']='Активность пользователей';
$lang['ADMCAB_VISSTAT_GUESTS']='Недавние гости (посл. 50)';
$lang['ADMCAB_VISSTAT_SPIDERS']='Активность поисковых машин';
$lang['ADMCAB_VISSTAT_SESSION']='Активные сессии';
$lang['ADMCAB_VISSTAT_LASTIP']='IP-Адрес';
$lang['ADMCAB_VGUESTS_GID']='Идентификатор';
$lang['ADMCAB_VGUESTS_VISIT']='Посещение';
$lang['ADMCAB_VGUESTS_STATUS']='Статус';
$lang['ADMCAB_VGUESTS_VIEWS']='Просм.';

$lang['ADMCAB_VSPIDERS_NAME']='Поисковик';
$lang['ADMCAB_VSPIDERS_MASK']='Маска UAgent';
$lang['ADMCAB_VSPIDERS_LASTTIME']='Посещение';
$lang['ADMCAB_VSPIDERS_VISITS']='Визтов';

$lang['ADMCAB_VUSERS_ID']='№';
$lang['ADMCAB_VUSERS_NICK']='Пользователь';
$lang['ADMCAB_VUSERS_VISIT']='Посещение';

$lang['ADMCAB_VSESS_USER']='Пользователь';
$lang['ADMCAB_VSESS_ID']='Идентификатор';
$lang['ADMCAB_VSESS_START']='Начало сессии';
$lang['ADMCAB_VSESS_LAST']='Посл. действие';
$lang['ADMCAB_VSESS_CLICKS']='Кликов';

$lang['ADMCAB_SECTIONS_HEADER']='Здесь Вы можете управлять структурой разделов форума QuickFox.';
$lang['ADMCAB_SECTION_EDIT_CAPT']='Редактирование раздела';
$lang['ADMCAB_SECTION_EDIT']='Внесите изменения в данные раздела и нажмите "Сохранить".';
$lang['ADMCAB_SECTION_NEW']='<b>Создание</b> нового раздела. Заполните следующие поля и нажмите "Сохранить".';
$lang['ADMCAB_SECTION_NEW_CAPT']='<b>Создание</b> нового раздела';
$lang['ADMCAB_SECTION_NEW_BTN']='Создать новый раздел';
$lang['ADMCAB_SECTION_SYNC']='Синхронизация';

$lang['ADMCAB_FORUMSYNC_REQUEST']='Если Вам кажется, что статистичекие данные или иерархия прав доступа ветвей или разделов нарушена, нажмите "Синхронизировать".';
$lang['ADMCAB_FORUMSYNC_COMPLETE']='Анализ данных форума завершен. Статистические данные и данные прав синхронизированы.
 Процесс занял <b>%s</b> сек. Произведено запросов SQL: <b>%s</b>.';

$lang['ADMCAB_DBDUMP_REQUEST']='Для начала резервного копирования содержимого базы данных заполните следующую форму:';
$lang['ADMCAB_DBDUMP_NOCONTENT']='Без содержимого?';
$lang['ADMCAB_DBDUMP_NOCONTENT_MORE']='Установите этот флаг, если не хотите сохранять данные о содержимом таблиц.';
$lang['ADMCAB_DBDUMP_NOSTRUCT']='Без структуры?';
$lang['ADMCAB_DBDUMP_NOSTRUCT_MORE']='Установите этот флаг, если не хотите сохранять данные о структуре таблицы.';
$lang['ADMCAB_DBDUMP_ALLTABLES']='Все таблицы?';
$lang['ADMCAB_DBDUMP_ALLTABLES_MORE']='Установите этот флаг, если хотите сохранить данные из всех таблиц (не только из принадлежащих QuickFox).';
$lang['ADMCAB_DBDUMP_ACCEPT']='Для начала резервного копирования нажмите "Пошел"';
$lang['ADMCAB_DBDUMP_READY']='Резервное копирование завершено. Вы можете получить файл резервной копии %sздесь%s';

$lang['ADMCAB_ACCGRP_REQUEST']='На данной странице вы можете изменять параметры уже существующих, а также создавать группы доступа данных системы.';
$lang['ADMCAB_ACCGRP_ID']='№';
$lang['ADMCAB_ACCGRP_NAME']='Имя';
$lang['ADMCAB_ACCGRP_DESCR']='Описание';
$lang['ADMCAB_ACCGRP_NUMUSERS']='Юзеров';
$lang['ADMCAB_ACCGRP_EDIT']='Правка';
$lang['ADMCAB_ACCGRP_GRL_EMPTY']='Ни одной группы доступа не задано';
$lang['ADMCAB_ACCGRP_CR_NEW']='Создать новую группу...';
$lang['ADMCAB_ACCGRP_US_NAME']='Пользователь';
$lang['ADMCAB_ACCGRP_T_GIVEN']='Доступ открыт';
$lang['ADMCAB_ACCGRP_T_DROP']='Доступ будет закрыт';
$lang['ADMCAB_ACCGRP_USLIST']='Список пользователей для группы';
$lang['ADMCAB_ACCGRP_USL_EMPTY']='Список пользователей пуст';
$lang['ADMCAB_ACCGRP_US_GOTPERM']='Перманентный доступ';
$lang['ADMCAB_ACCGRP_US_SELDO']='С выделенными';
$lang['ADMCAB_ACCGRP_US_DROP']='Закрыть доступ';
$lang['ADMCAB_ACCGRP_US_SETMONTH']='Установить доступ на месяц';
$lang['ADMCAB_ACCGRP_US_SETPERM']='Установить перманентный доступ';
$lang['ADMCAB_ACCGRP_US_ADD_TO']='Открыть пользователям доступ к группе данных';
$lang['ADMCAB_ACCGRP_US_ADD_REQUEST']='Введите список пользователей, разделенный ЗАПЯТЫМИ';
$lang['ADMCAB_ACCGRP_US_ADD_WITHDO']='С указанными';

$lang['ADMCAB_ACCGRP_GR_DELCAPT']='Удаление группы.';
$lang['ADMCAB_ACCGRP_GR_DELETE']='Вы собираетесь удалить группу <b>%s</b>! Нажмите <a href="%s">здесь</a> чтобы это сделать.';

$lang['ADMCAB_ACCGRP_ERR_NOGROUP']='Указанная группа не найдена.';
$lang['ADMCAB_ACCGRP_ERR_NONAME']='Не задано имя группы.';
$lang['ADMCAB_ACCGRP_ERR_NAMEDUP']='Группа с таким именем уже существует.';
$lang['ADMCAB_ACCGRP_GR_EDITED']='Группа <b>%s</b> успешно отредактирована.';
$lang['ADMCAB_ACCGRP_GR_ADDED']='Группа <b>%s</b> успешно создана.';

$lang['ADMCAB_ACCGRP_US_ADDED']='Указанным пользователям открыт доступ к группе <b>%s</b>. Обработано пользователей: <b>%s</b>.';
$lang['ADMCAB_ACCGRP_US_EDITED']='Параметры доступа указанных пользователей к группе <b>%s</b> изменены. Обработано пользователей: <b>%s</b>.';

// Config langs
$lang['CONFIG_COMMON_REQUEST']='Пожалуйста заполните/измените следующие поля, содержащие информацию о сайте а также общие настройки системы.';
$lang['CONFIG_COMMON_COMMCONF']='Общие настройки системы.';
$lang['CONFIG_COMMON_SITENAME']='Название сайта:';
$lang['CONFIG_COMMON_SITENAME_MORE']='Заголовок сайта. Используется в заголовках генерируемых страниц и почтовых сообщений.';
$lang['CONFIG_COMMON_SITENAME_ERR']='Заголовок сайта задан неверно.';

$lang['CONFIG_COMMON_SITEMAIL']='E-Mail адрес администрации:';
$lang['CONFIG_COMMON_SITEMAIL_MORE']='Адрес электронной почты, который будет использован в административных целях.';
$lang['CONFIG_COMMON_SITEMAIL_ERR']='Адрес электронной почты задан неверно.';

$lang['CONFIG_COMMON_SITE_STYLE']='Стиль интерфейса:';
$lang['CONFIG_COMMON_SITE_STYLE_MORE']='Стиль интерфейса, используемый по-умолчанию.';
$lang['CONFIG_COMMON_SITE_STYLE_ERR']='Заданный стиль интерфейса не существует.';
$lang['CONFIG_COMMON_CSSSEP']='Отправлять CSS/JS отдельно.';
$lang['CONFIG_COMMON_CSSSEP_MORE']='При включение данной опции QuickFox не будет встраивать CSS/JS данные в страницу.
 Эти данные будут запрашиваться клиентом в отдельном потоке. В таком случае они могут быть кэшированы на стороне клиента.';

$lang['CONFIG_COMMON_SITE_GZIP']='Использовать GZIP?';
$lang['CONFIG_COMMON_SITE_GZIP_MORE']='Использование технологии сжатия GZIP позволит съэкономить траффик, как пользователям, так и серверу в целом.
 Сжатие будет применяться только в случае поддержки его клиентом пользователя.';
$lang['CONFIG_COMMON_SITE_SMTP']='Использовать SMTP?';
$lang['CONFIG_COMMON_SITE_SMTP_MORE']='Использование протокола SMTP позволяет обойтись без запуска дополнительного процесса sendmail при отправке почты.
 В случае неработоспособности уведомлений отключите эту опцию.';
$lang['CONFIG_COMMON_SITE_DOGUESTS']='Работа с гостями.';
$lang['CONFIG_COMMON_SITE_DOGUESTS_MORE']='При включении данной опции все гости сайта записываются и получают временный уникальный идентификатор.
 Это позволяет обеспечить большее удобство в работе.';
$lang['CONFIG_COMMON_SITE_DOSPIDERS']='Работа с поисковыми машинами.';
$lang['CONFIG_COMMON_SITE_DOSPIDERS_MORE']='При включении данной опции QuickFox будет пытаться распознать поисковую машину в гостевом запросе.
 В случае опознания таковой будет сгенерирована страница, содержимое которой подготовлено специально для поисковых машин.
 Это позволяет съэкономить время, затрачиваемое на работу с роботами, а также повысить качество индексирования.';
$lang['CONFIG_COMMON_SITE_NOSPIDERS']='Запретить индексацию.';
$lang['CONFIG_COMMON_SITE_NOSPIDERS_MORE']='При включении данной опции QuickFox не будет выдавать информацию распознанным поисковым машинам.
 Имеет значение только при включенной опции "Работа с поисковыми машинами".';
$lang['CONFIG_COMMON_SITE_REGAPPROVE'] = 'Проверка аккаунтов администратором.';
$lang['CONFIG_COMMON_SITE_REGAPPROVE_MORE'] = 'Устанавливает ограничение для вновь регистрируемых пользователей. Кажды новый аккаунт должен быть проверен администратором.';
$lang['CONFIG_COMMON_SITE_UINFOACC']='Доступ к информации о пользователях.';
$lang['CONFIG_COMMON_SITE_UINFOACC_MORE']='Устанавливает уровень доступа к информации о зарегистрированных пользователях.';
$lang['CONFIG_COMMON_SITE_UINFOACC_ERR']='Уровень доступа к информации о пользователях задан неверно.';
$lang['CONFIG_COMMON_SITE_NOSPAM']='Защита от спама.';
$lang['CONFIG_COMMON_SITE_NOSPAM_MORE']='Система защиты от спама позволяет избежать нежелательного появления спама (информационного мусора) в форуме и других частях сайта.
 Для работы системы требуется наличие установленной в PHP библиотеки GD.';

$lang['CONFIG_COMMON_TIMECONF']='Настройки времени и даты.';
$lang['CONFIG_COMMON_SITE_TZ']='Часовой пояс:';
$lang['CONFIG_COMMON_SITE_TZ_MORE']='Часовой пояс по-умолчанию. Система использует данную настройку при отображении информации о времени и датах.';
$lang['CONFIG_COMMON_SITE_TZ_ERR']='Часовой пояс задан неверно.';

$lang['CONFIG_COMMON_DATEFORM']='Формат даты:';
$lang['CONFIG_COMMON_DATEFORM_MORE']='Формат отображения полной даты в системе. Синтаксис идентичен функции <a href="http://www.php.net/date">date()</a> языка PHP.';
$lang['CONFIG_COMMON_DATEFORM_ERR']='Формат отображения полной даты задан неверно.';

$lang['CONFIG_COMMON_TIMEFORM']='Формат времени:';
$lang['CONFIG_COMMON_TIMEFORM_MORE']='Формат отображения времени суток в системе. Синтаксис идентичен функции <a href="http://www.php.net/date">date()</a> языка PHP.';
$lang['CONFIG_COMMON_TIMEFORM_ERR']='Формат отображения времени задан неверно.';

$lang['CONFIG_COMMON_TIMECORR']='Коррекция времени:';
$lang['CONFIG_COMMON_TIMECORR_MORE']='Эта опция позволяет скорректировать отображение времени на сайте в случае ошибочной настройки времени на сервере. При отображении времени оно будет скорректировано на указанное количество минут.';

$lang['CONFIG_COMMON_FILESCONF']='Параметры загрузки файлов.';
$lang['CONFIG_COMMON_FILES_PRIGHTS']='Уровень доступа для загрузки файлов:';
$lang['CONFIG_COMMON_FILES_PRIGHTS_MORE']='Задает минимальный уровень доступа для загзузки файлов на сервер.';
$lang['CONFIG_COMMON_FILES_PRIGHTS_ERR']='Уровень доступа для загрузки файлов задан неверно.';

$lang['CONFIG_COMMON_FILES_NOATTC']='Отправлять файлы с заголовком Inline:';
$lang['CONFIG_COMMON_FILES_NOATTC_MORE']='Позволяет браузеру самому отображать файлы вместо принудительного открытия диалога сохранения файла при скачивании.';

$lang['CONFIG_COMMON_FILES_MSIZE']='Предельный размер файлов:';
$lang['CONFIG_COMMON_FILES_MSIZE_MORE']='Задает максимальный размер файлов, принимаемых QuickFox при загрузке на сервер. (512 - 102400 КБайт)<br />
 Дополнительно следует учесть ограничения, указанные при настройке PHP.';
$lang['CONFIG_COMMON_FILES_MSIZE_ERR']='Предельный размер файлов задан неверно.';

$lang['CONFIG_COMMON_FILES_TWIDTH']='Ширина миниатюр:';
$lang['CONFIG_COMMON_FILES_TWIDTH_MORE']='Задает максимальный размер миниатюры по горизонтали. (80 - 200 пикс)';
$lang['CONFIG_COMMON_FILES_TWIDTH_ERR']='Ширина миниатюр задана неверно.';

$lang['CONFIG_COMMON_FILES_THEIGHT']='Высота миниатюр:';
$lang['CONFIG_COMMON_FILES_THEIGHT_MORE']='Задает максимальный размер миниатюры по вертикали. (80 - 200 пикс)';
$lang['CONFIG_COMMON_FILES_THEIGHT_ERR']='Высота миниатюр задана неверно.';

$lang['CONFIG_COMMON_ACCEPT']='Для сохранения настроек нажмите "Подтвердить".';

$lang['CONFIG_FORUM_REQUEST']='Пожалуйста заполните/измените следующие поля, содержащие основные настройки форума.';

$lang['CONFIG_FORUM_ROOTNAME']='Заголовок корневого раздела:';
$lang['CONFIG_FORUM_ROOTNAME_MORE']='Данная опция позволяет переименовать корневой раздел форума. Для использования стандартного заголовка "Корневой раздел" оставьте поле пустым.';
$lang['CONFIG_FORUM_ROOTNAME_ERR']='Заголовок корневого раздела задан неверно.';

$lang['CONFIG_FORUM_PAGEPOSTS']='Сообщений на странице:';
$lang['CONFIG_FORUM_PAGEPOSTS_MORE']='Данная опция задает количество сообщений отображаемое на одной странице при просмотре тем форума.';
$lang['CONFIG_FORUM_PAGEPOSTS_ERR']='Количество сообщений на странице задано неверно.';

$lang['CONFIG_FORUM_MESSLOCK']='Время фиксации (мин):';
$lang['CONFIG_FORUM_MESSLOCK_MORE']='Данная опция задает отрезок времени (в минутах), втечение которого сообщение является "нефиксированным".
 Редактирование такого сообщения считается незначительным и информация об этом не записывается в историю вариантов.
 Также в случае создания нового сообщения тем же автором в той же теме оно "приклеивается" к нефиксированному.
 <br />* Сообщение фиксируется досрочно в случае редактирования его модератором либо поступления нового ответа в тему от другого пользователя.';
$lang['CONFIG_FORUM_MESSLOCK_ERR']='Время фиксации задано неверно.';

$lang['CONFIG_FORUM_GBOOK']='Гостевая книга:';
$lang['CONFIG_FORUM_GBOOK_MORE']='Данная опция задает тему форума, являющуюся гостевой книгой сайта.
 Гостевая книга автоматически получает отдельную ссылку в главном меню.
 В списке вариантов вы видите темы со свободним доступом по записи.';
$lang['CONFIG_FORUM_GBOOK_ERR']='Гостевая книга задана неверно.';

$lang['CONFIG_FORUM_POSTFILES']='Прикреплять файлов:';
$lang['CONFIG_FORUM_POSTFILES_MORE']='Данная опция задает максимальное количество файлов, которое можно прикрепить во время отправки или редактирования сообщения.';
$lang['CONFIG_FORUM_POSTFILES_ERR']='Количество прикрепляемых файлов задано неверно.';

$lang['CONFIG_FORUM_ACCEPT']='Для сохранения настроек форума нажмите "Подтвердить".';


$lang['CONFIG_VISUAL_REQUEST']='Пожалуйста заполните/измените следующие поля, содержащие параметры отображаемых на странице блоков.';
$lang['CONFIG_VISUAL_FIRST']='Логотип и меню.';

$lang['CONFIG_VISUAL_SITELOGO']='Логотип сайта:';
$lang['CONFIG_VISUAL_SITELOGO_MORE']='Ссылка на изображение, используемое в качетсве логотипа сайта.';
$lang['CONFIG_VISUAL_SITELOGO_ERR']='Логотип задан неверно или файл не сеществует.';

$lang['CONFIG_VISUAL_MHIDE_HOME']='Скрыть "На главную".';
$lang['CONFIG_VISUAL_MHIDE_HOME_MORE']='Скрывает элемент меню "На главную".';
$lang['CONFIG_VISUAL_MHIDE_GBOOK']='Скрыть "Гостевая".';
$lang['CONFIG_VISUAL_MHIDE_GBOOK_MORE']='Скрывает ссылку на гостевую книгу.';
$lang['CONFIG_VISUAL_MHIDE_FORUM']='Скрыть "Форум".';
$lang['CONFIG_VISUAL_MHIDE_FORUM_MORE']='Скрывает ссылку на форум.';
$lang['CONFIG_VISUAL_MHIDE_USERS']='Скрыть "Пользователи".';
$lang['CONFIG_VISUAL_MHIDE_USERS_MORE']='Скрывает ссылку на список пользователей.';
$lang['CONFIG_VISUAL_MADD_BUTTS']='Добавить элементы меню:';
$lang['CONFIG_VISUAL_MADD_BUTTS_MORE']='Добавляет дополнительные кнопки в меню.
 Описание каждой кнопки занимает отдельную строку.
 Каждая строка должна содержать URL ссылки для кнопки и далее, через пробел, заголовок кнопки.';
$lang['CONFIG_VISUAL_MADD_BUTTS_ERR']='Ошибка при задании списка дополнительных элементов меню.';

$lang['CONFIG_VISUAL_ADVS']='Рекламно баннерные блоки.';

$lang['CONFIG_VISUAL_ADVDATA']='Боковой рекламный блок:';
$lang['CONFIG_VISUAL_ADVDATA_MORE']='Содержимое рекламного блока в боковой колонке.
 Используется для размещения небольших баннеров.<br />
 <span class="red"><b>Внимание!</b> Правильность структуры HTML не проверяется.</span>';
$lang['CONFIG_VISUAL_BADVDATA']='Нижний рекламный блок:';
$lang['CONFIG_VISUAL_BADVDATA_MORE']='Содержимое рекламного блока внизу страницы.
 Используется для размещения больших баннеров.<br />
 <span class="red"><b>Внимание!</b> Правильность структуры HTML не проверяется.</span>';

$lang['CONFIG_VISUAL_ACCEPT']='Для сохранения настроек нажмите "Подтвердить".';

?>
