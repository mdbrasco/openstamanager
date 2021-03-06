<?php

include_once __DIR__.'/../../core.php';

// Disabilito il tasto di firma per gli interventi completati
if ($records[0]['flg_completato']) {
    $disabled = 'disabled';
    $readonly = 'readonly';
} else {
    $disabled = '';
    $readonly = '';
}

if (empty($records[0]['firma_file'])) {
    $frase = tr('Anteprima e firma');
    $info_firma = '';
} else {
    $frase = tr('Nuova anteprima e firma');
    $info_firma = '<span class="label label-success"><i class="fa fa-edit"></i> '.tr('Firmato il _DATE_ alle _TIME_ da _PERSON_', [
        '_DATE_' => Translator::dateToLocale($records[0]['firma_data']),
        '_TIME_' => Translator::timeToLocale($records[0]['firma_data']),
        '_PERSON_' => '<b>'.$records[0]['firma_nome'].'</b>',
    ]).'</span>';
}

echo '

<!-- EVENTUALE FIRMA GIA\' EFFETTUATA -->
'.$info_firma.'

<button type="button" class="btn btn-primary " onclick="launch_modal( \''.tr('Anteprima e firma').'\', globals.rootdir + \'/modules/interventi/add_firma.php?id_module='.$id_module.'&id_record='.$id_record.'&anteprima=1\', 1 );" '.$disabled.'>
    <i class="fa fa-desktop"></i> '.$frase.'...
</button>';
