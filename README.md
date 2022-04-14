# Модуль ConcordPay для Joomla EShop

Creator: [ConcordPay](https://concordpay.concord.ua)<br>
Tags: ConcordPay, Joomla, EShop, payment, payment gateway, credit card, Visa, Masterсard, Apple Pay, Google Pay<br>
Requires at least: Joomla 3.8, EShop 3.3<br>
License: GNU GPL v3.0<br>
License URI: [License](https://opensource.org/licenses/GPL-3.0)

Этот модуль позволит вам принимать платежи через платёжную систему **ConcordPay**.

Для работы модуля у вас должны быть установлены **CMS Joomla 3.x-4.x** и модуль электронной коммерции **EShop 3.x**.

**Обратите внимание!**<br>
Модуль не является стандартным плагином **Joomla**, а представляет собой платёжный плагин к компоненту **EShop**, требующий иного способа установки.

## Установка

### Установка через загрузку модуля

1. В административной части сайта перейти в *«Components -> EShop -> Plugins -> Payments»* и в разделе *«Install New Payment Plugin»* загрузить архив с модулем,
   который находится в папке `package`.

2. Зайти в настройки плагина **os_concordpay**.

3. Установить необходимые настройки плагина.<br>
   Состояние: **Включено**<br>

   Указать данные, полученные от платёжной системы:
    - *Идентификатор торговца (Merchant ID)*;
    - *Секретный ключ (Secret Key)*.

   Также установить:
    - *Статусы заказа на разных этапах его существования*;
    - *Язык страницы оплаты ConcordPay*.

5. Сохранить настройки модуля.

Модуль готов к работе.

### Деинсталляция

1. В административной части сайта в разделе *«Components -> EShop -> Plugins -> Payments»* найти плагин **os_concordpay**.

2. В столбце *Status* возле модуля выбрать в выпадающем списке операцию *Delete* и удалить плагин.

Убедиться, что в каталоге `components/com_eshop/plugins/payment` нет файлов модуля.

*Модуль Joomla EShop протестирован для работы с Joomla 3.8.10 и 4.1.2, EShop 3.5.4, PHP 7.4.*