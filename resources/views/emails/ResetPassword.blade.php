@component('mail::message')
<p>Terimakasih telah menggunakan layanan OCR Farcapital<br>untuk mengubah password anda, silahkan klik tombol dibawah ini.</p>

@component('mail::button', ['url' => $token])
Ganti Password
@endcomponent

<p>Link ini akan kadaluarsa dalam 3 jam, untuk mendapatkan link baru kunjungi <a href="http://localhost:8080/mbuhopoiki">http://localhost:8080/mbuhopoiki</a></p>
Thanks,<br>
{{ config('app.name') }}
@endcomponent
