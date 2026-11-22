<?php

declare(strict_types=1);

return [
    'action' => 'Länder importieren',
    'manual' => 'Neues Land (erweitert)',
    'upload' => 'Datensatz hochladen',
    'dataset' => 'Länder-Datensatz',
    'format_help' => 'Laden Sie JSON-Länderzeilen (oder ein Array „countries“) oder eine CSV-Datei mit den Spalten name, iso2 und iso3 hoch. Maximal 2 MB.',
    'restore' => 'Passende gelöschte Länder wiederherstellen',
    'disable_missing' => 'Aktive Länder deaktivieren, die in diesem Datensatz fehlen',
    'review' => 'Änderungen prüfen',
    'review_help' => 'Es wurde noch nichts gespeichert. Übersprungene Zeilen umfassen unveränderte Länder und ungültige Zeilen. Gehen Sie zurück, um die Datei zu ersetzen oder Optionen zu ändern, und prüfen Sie dann erneut.',
    'apply' => 'Import anwenden',
    'apply_failed' => 'Der Import wurde nicht angewendet',
    'conflicting_rows' => 'Es wurden keine Änderungen gespeichert. Prüfen Sie, ob doppelte ISO-Codes oder passende gelöschte Länder vorhanden sind. Aktivieren Sie bei Bedarf die Wiederherstellung und prüfen Sie dann erneut.',
    'confirm_disable' => 'Ich bestätige, dass :count aktive Länder deaktiviert werden.',
    'invalid_file' => 'Wählen Sie einen gültigen JSON- oder CSV-Länderdatensatz aus und prüfen Sie ihn erneut.',
    'review_changed' => 'Der Datensatz, die Optionen oder die Länder haben sich geändert. Gehen Sie zurück und prüfen Sie den Import erneut, bevor Sie ihn anwenden.',
    'no_active_countries' => 'Es sind keine aktiven Länder verfügbar. Importieren Sie Länder oder bitten Sie einen Administrator, eines zu aktivieren, und kehren Sie dann zu dieser Adresse zurück.',
    'completed' => 'Der Länderimport wurde abgeschlossen. Geänderte Länderdatensätze: :changed.',
    'dry_run' => 'Der Testlauf des Länderimports hat Länderdatensätze gefunden, die geändert würden: :changed.',
    'path_required' => 'Ein Pfad zum Länder-Datensatz ist erforderlich.',
    'counts' => [
        'created' => 'Erstellt: :count',
        'updated' => 'Aktualisiert: :count',
        'restored' => 'Wiederhergestellt: :count',
        'disabled' => 'Deaktiviert: :count',
        'skipped' => 'Übersprungen: :count',
    ],
];
