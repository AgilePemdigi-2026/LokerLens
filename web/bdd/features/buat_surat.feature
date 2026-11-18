# Sumber: SRS LokerLens, Appendix B, Table B.5 (US-04).
Feature: Buat surat teguran/laporan polisi

  Background:
    Given I am logged in as "Kepala Bidang"

  Scenario: Menerbitkan laporan polisi
    Given I am on the letter page for ticket "#LKR-8770"
    When I fill in "kronologi" with "Pelapor menerima surat panggilan interview fiktif atas nama PT Maju Petro Energy"
    And I select "Subdit Siber Ditreskrimsus Polda Metro Jaya / Wilayah Terkait" from "tujuan_kepolisian"
    And I press "Terbitkan Laporan"
    Then I should see "Kasus terdokumentasi otomatis"
    And I should see "Kasus selesai"

  Scenario: Menyimpan surat sebagai draf
    Given I am on the letter page for ticket "#LKR-8770"
    When I fill in "kronologi" with "Draf kronologi sementara"
    And I press "Simpan Draft"
    Then I should see "Draf berhasil disimpan"

  Scenario: Gagal menerbitkan dokumen
    Given I am on the letter page for ticket "#LKR-8770"
    And penerbitan dokumen akan gagal diproses sistem
    When I press "Terbitkan Laporan"
    Then I should see "Gagal menerbitkan dokumen, draf telah disimpan"
    And I should see "Buat Laporan Polisi"

  # Skenario di bawah BARU (issue #8): halaman Surat untuk draf dan surat terbit.
  # Belum ada di SRS Appendix B Table B.5, perlu ditambahkan (lihat docs/laporan_kondisi_bdd.md bagian 9).
  Scenario: Melihat daftar surat
    When I follow "Surat"
    Then I should see "Daftar Surat Teguran/Laporan Polisi"
    And I should see "#LKR-8770"
    And I should see "#LKR-8798"
    And I should see "Lanjutkan Draf"
    And I should see "Lihat Surat"

  Scenario: Melanjutkan draf surat
    Given I am on the "/surat" page
    When I follow "Lanjutkan Draf" in the row for ticket "#LKR-8770"
    Then the url should match "/buat-surat/\d+$"
    And I should see "Buat Laporan Polisi"
    And the "kronologi" field should contain "Pelapor diminta mentransfer biaya pelatihan Rp250.000 sebelum mulai bekerja."

  Scenario: Memfilter surat berdasarkan status
    Given I am on the "/surat" page
    When I select "Terbit" from "status"
    And I press "Cari"
    Then I should see "#LKR-8798"
    And I should not see "#LKR-8770"

  Scenario: Peran lain tidak bisa membuka halaman Surat
    Given I am logged in as "Analis"
    When I am on the "/surat" page
    Then the response status code should be 403

  # Skenario di bawah BARU (issue #8): pilihan jenis dokumen Surat Teguran.
  # Belum ada di SRS Appendix B Table B.5 (lihat docs/laporan_kondisi_bdd.md bagian 10).
  Scenario: Menerbitkan surat teguran
    Given I am on the letter page for ticket "#LKR-8770"
    When I select "Surat Teguran" from "jenis_dokumen"
    And I fill in "kronologi" with "Perusahaan memungut biaya pelatihan dari calon pekerja sebelum mulai bekerja"
    And I fill in "tujuan_teguran" with "Pimpinan PT Gemilang Karya Persada"
    And I press "Terbitkan Laporan"
    Then I should see "Surat Teguran Berhasil Diterbitkan"
    And I should see "Kasus terdokumentasi otomatis"
    And I should see "Kasus selesai"

  Scenario: Menyimpan surat teguran sebagai draf
    Given I am on the letter page for ticket "#LKR-8770"
    When I select "Surat Teguran" from "jenis_dokumen"
    And I fill in "kronologi" with "Draf teguran sementara"
    And I fill in "tujuan_teguran" with "Direktur PT Gemilang Karya Persada"
    And I press "Simpan Draft"
    Then I should see "Draf berhasil disimpan"
    And the "jenis_surat_teguran" checkbox should be checked
    And the "tujuan_teguran" field should contain "Direktur PT Gemilang Karya Persada"
