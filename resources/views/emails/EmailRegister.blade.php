@component('mail::message')
Terimakasih telah menggunakan layanan OCR Farcapital<br>
untuk konfirmasi email anda, silahkan klik tombol dibawah ini.
@component('mail::button', ['url' => $link])
Ganti Password
@endcomponent
Thanks,<br>
{{ config('app.name') }}
@endcomponent
