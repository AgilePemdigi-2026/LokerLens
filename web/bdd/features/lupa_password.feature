# Sumber: user story BARU US-05, Table B.6 (fitur Sprint 2). Belum ada di SRS LokerLens Appendix B, perlu ditambahkan ke SRS.
Feature: Lupa password
  As a Pengguna, I want to mengatur ulang kata sandi melalui tautan yang dikirim ke email,
  so that saya dapat kembali masuk ke akun ketika lupa kata sandi.

  Scenario: Meminta tautan reset dengan email yang terdaftar
    Given I am on the "/forgot-password" page
    When I fill in "email" with "adelulinn@gmail.com"
    And I press "Kirim Tautan Reset Kata Sandi"
    Then I should see "Periksa email Anda"

  Scenario: Email yang tidak terdaftar tetap mendapat pesan yang sama
    Given I am on the "/forgot-password" page
    When I fill in "email" with "tidak.terdaftar@contoh.com"
    And I press "Kirim Tautan Reset Kata Sandi"
    Then I should see "Periksa email Anda"

  Scenario: Gagal meminta tautan karena email kosong
    Given I am on the "/forgot-password" page
    When I press "Kirim Tautan Reset Kata Sandi"
    Then I should see "Email wajib diisi."
    And I should not see "Periksa email Anda"

  Scenario: Mengatur kata sandi baru dengan tautan yang sah
    Given I am on a valid password reset page for "adelulinn@gmail.com"
    When I fill in "email" with "adelulinn@gmail.com"
    And I fill in "password" with "sandiBaru123"
    And I fill in "password_confirmation" with "sandiBaru123"
    And I press "Simpan Kata Sandi Baru"
    Then I should see "Kata sandi berhasil diubah"
    When I am on the "/login" page
    And I fill in "email" with "adelulinn@gmail.com"
    And I fill in "password" with "sandiBaru123"
    And I press "Masuk"
    Then I should be on "/beranda"

  Scenario: Gagal mengatur kata sandi karena tautan tidak sah
    Given I am on the "/reset-password/token-tidak-sah?email=adelulinn@gmail.com" page
    When I fill in "email" with "adelulinn@gmail.com"
    And I fill in "password" with "sandiBaru123"
    And I fill in "password_confirmation" with "sandiBaru123"
    And I press "Simpan Kata Sandi Baru"
    Then I should see "Tautan atur ulang kata sandi tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru."
