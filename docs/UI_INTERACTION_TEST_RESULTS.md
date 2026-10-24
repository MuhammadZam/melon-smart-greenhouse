# UI Interaction Test Results

Tanggal: **24 Oktober 2026**, project existing `D:\Compro`. Refinement presentation-only berdasarkan arahan putih, minimalis, flat dan data-first.

## PASS — 68 pemeriksaan browser

52 pemeriksaan alur utama dan16 pemeriksaan state/CRUD dijalankan melalui Playwright pada database salinan yang terpisah dari data utama. Halaman dibuka melalui server Laravel nyata, bukan static mockup. Mock network hanya digunakan untuk memicu error/retry notifikasi dan menahan POST login sebentar agar state loading bisa diperiksa. CSV/PDF diunduh dari endpoint nyata.

| Kelompok | Bukti hasil |
| --- | --- |
| Login/logout | Required validation,admin session,logout ke login PASS |
| Navigasi mobile | Open,focus containment,Tab wrap,Escape,overlay dan focus return375/768 PASS |
| Notifikasi | Bounds viewport,Escape,error feedback dan retry recovery PASS |
| Tabel/filter | ArrowRight scroll,pagination2,monitoring/plants/alarm/fuzzy/reports apply/reset PASS |
| Validasi | Native required dan server error association PASS |
| Grafik | 13 slot initialized atau truthful empty-state,resize4 viewport,tooltip,legend toggle,plant/date filters PASS |
| Ekspor | CSV174384 bytes,PDF209295 bytes;download signature valid |
| Simulasi | Modal open,focus,Escape,batal,konfirmasi dan success feedback PASS |
| Sheets | Mode mock,sync feedback PASS;tidak menguji Google live |
| Button states | Exact primary RGB,radius4,hover,pressed matrix.98,focus,opacity.5/not-allowed,loading PASS |
| CRUD tanaman | Create/edit fixture,delete-dismiss dan delete-confirm PASS;hanya salinan DB |
| Reduced motion | Transition0s saat prefers-reduced-motion PASS |
| Sensor | Delapan neutral cards,8 timestamps,null N/A,theme chart konsisten PASS |

## Pemeriksaan per kasus

| Pemeriksaan | Hasil |
| --- | --- |
| login required validation | PASS |
| admin login | PASS |
| drawer open 375 | PASS |
| drawer focus 375 | PASS |
| drawer focus wraps 375 | PASS |
| drawer escape focus return 375 | PASS |
| drawer overlay closes 375 | PASS |
| notifications contained 375 | PASS |
| notification escape 375 | PASS |
| drawer open 768 | PASS |
| drawer focus 768 | PASS |
| drawer focus wraps 768 | PASS |
| drawer escape focus return 768 | PASS |
| drawer overlay closes 768 | PASS |
| notifications contained 768 | PASS |
| notification escape 768 | PASS |
| table keyboard horizontal scroll | PASS |
| monitoring empty filter | PASS |
| monitoring filter reset | PASS |
| pagination available | PASS |
| pagination page 2 | PASS |
| plant required validation | PASS |
| server validation linked to field | PASS |
| 13 chart slots initialized or truthful empty state | PASS |
| chart resize 375 | PASS |
| chart resize 768 | PASS |
| chart resize 1366 | PASS |
| chart resize 1920 | PASS |
| chart tooltip on observation | PASS |
| chart legend toggles series | PASS |
| chart empty state | PASS |
| dashboard plant filter submitted | PASS |
| report date filter submitted | PASS |
| report filter reset | PASS |
| csv export download | PASS |
| pdf export download | PASS |
| simulation modal opens | PASS |
| modal keyboard focus | PASS |
| modal escape restores focus | PASS |
| modal cancel | PASS |
| simulation success feedback | PASS |
| sheets mode visible | PASS |
| mock Sheets sync feedback | PASS |
| /plants filter applies | PASS |
| /plants reset | PASS |
| /alarms filter applies | PASS |
| /alarms reset | PASS |
| /fuzzy/history filter applies | PASS |
| /fuzzy/history reset | PASS |
| notification network error feedback | PASS |
| notification retry recovers | PASS |
| logout returns to login | PASS |
| primary default palette and radius | PASS |
| primary hover feedback | PASS |
| primary pressed feedback | PASS |
| keyboard focus visible | PASS |
| disabled feedback | PASS |
| login loading feedback | PASS |
| plant create feedback | PASS |
| plant edit feedback | PASS |
| delete cancellation asks confirmation | PASS |
| confirmed fixture delete feedback | PASS |
| reduced motion respected | PASS |
| sensor cards share neutral presentation | PASS |
| eight sensor timestamps retained | PASS |
| null sensor values remain unavailable | PASS |
| charts use consistent flat palette | PASS |
| public home primary navigation | PASS |

## Regresi dan batas

php artisan test: **297 passed,2075 assertions**,0 failure/error/skipped. Tidak mengubah tests agar ekspektasi bisnis lulus; label section existing dipertahankan pada accessible region. Build production PASS. Syntax compiled Blade diperiksa. Source backend/config/schema/routes/tests tetap identik dengan baseline refinement.

Artifacts: [alur52](ui_flat/evidence/interactions.json), [state16](ui_flat/evidence/component-states.json), [JUnit](ui_flat/evidence/regression.xml), [build](ui_flat/evidence/build.txt), [axe22 halaman](ui_flat/evidence/accessibility.json). Axe akhir0 detected violations; incomplete bukan PASS manual. Bukan uji semua kombinasi data/role/perangkat fisik. Protected delete tetap memberi penjelasan retention; tidak menghapus histori utama.
