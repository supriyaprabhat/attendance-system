<?php

use Illuminate\Support\Str;

return [

    /** App Setting */
    'authorize-login' => 'Авторизованный вход',
    'override-bssid' => 'Перезаписать bssid',
    '24-hour-format' => '24-часовой формат',
    'bs' => 'Дата по BS',
    'attendance-note' => 'Примечание по присутствию',


    /** General Setting */

    'firebase_key' => 'Ключ Firebase',
    'firebase_key_description' => 'Ключ Firebase необходим для отправки уведомлений на мобильное устройство.',
    'attendance_notify' => 'Установите количество дней для локальных push-уведомлений',
    'attendance_notify_description' => 'Установка количества дней автоматически отправит данные этих дней в мобильное приложение. Получение этих данных на мобильном устройстве позволит мобильному приложению настроить локальные push-уведомления для этих дат. Локальные push-уведомления помогут сотрудникам помнить о своевременном входе и выходе, а также о выходе, когда смена близка к окончанию.',
    'advance_salary_limit' => 'Лимит авансовой зарплаты (%)',
    'advance_salary_limit_description' => 'Установите максимальную сумму в процентах, которую сотрудник может запросить в качестве аванса, исходя из общей зарплаты.',
    'employee_code_prefix' => 'Префикс кода сотрудника',
    'employee_code_prefix_description' => 'Этот префикс будет использоваться для создания кода сотрудника.',
    'attendance_limit' => 'Лимит посещаемости',
    'attendance_limit_description' => 'Лимит посещаемости для регистрации и выхода.',
    'award_display_limit' => 'Лимит отображения наград',
    'award_display_limit_description' => 'Лимит отображения наград в мобильном приложении.',
];