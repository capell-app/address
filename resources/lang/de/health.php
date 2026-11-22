<?php

declare(strict_types=1);

return [
    'storage_tables_label' => 'Adresse: Datenbanktabellen vorhanden',
    'storage_tables_passed' => 'Die Tabellen für Länder und Adressen sind vorhanden.',
    'storage_tables_failed' => 'Fehlende Tabellen: :tables.',
    'storage_tables_remediation' => 'Führen Sie die Migrationen des Address-Pakets aus, bevor Sie Adressdatensätze verwenden.',
    'data_quality_label' => 'Adresse: Adressdetails vollständig',
    'data_quality_missing_tables' => 'Die Datenqualität der Adressen konnte nicht geprüft werden, da Speichertabellen fehlen.',
    'data_quality_missing_tables_remediation' => 'Führen Sie die Migrationen des Address-Pakets aus und starten Sie die Diagnose anschließend erneut.',
    'data_quality_passed' => 'Geprüfte Adressen: :addresses. Es wurden keine blockierenden Probleme mit Ländern oder Koordinaten gefunden.',
    'data_quality_failed' => 'Geprüfte Adressen: :addresses. Adressen ohne aktiviertes Land: :countries. Probleme mit ungültigen Koordinaten: :coordinates.',
    'data_quality_remediation' => 'Prüfen Sie Adressdatensätze mit fehlenden Ländern oder ungültigen Breiten-/Längengrad-Metadaten.',
    'duplicate_addresses_label' => 'Adresse: Adressduplikate geprüft',
    'duplicate_addresses_missing_tables' => 'Die Qualität bei Adressduplikaten konnte nicht geprüft werden, da Speichertabellen fehlen.',
    'duplicate_addresses_passed' => 'Geprüfte Adressen: :addresses. Es wurden keine wahrscheinlichen Duplikatgruppen gefunden.',
    'duplicate_addresses_failed' => 'Geprüfte Adressen: :addresses. Adressen in wahrscheinlichen Duplikatgruppen: :duplicates, verteilt auf :groups Gruppen.',
    'duplicate_addresses_remediation' => 'Prüfen Sie die Gruppen doppelter Adressen, bevor Sie Workflows zum Zusammenführen oder Bereinigen hinzufügen.',
    'providers_label' => 'Adresse: optionale Adressdienste erkannt',
    'providers_message' => 'Erkannte Validierungsdienste: :validation. Erkannte Koordinatendienste: :geocoding. Konfigurieren Sie einen Adressdienst nur, wenn Ihre Website ihn benötigt.',
];
