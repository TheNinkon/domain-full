<x-mail::message>
# It works! 🎉

Este es un email de prueba enviado desde la configuración de SMTP de
**{{ config('variables.templateName') }}**.

Si lo recibiste, las credenciales de email están funcionando correctamente y todos los
avisos del sistema (ofertas recibidas, dominios por vencer) se enviarán con esta misma
configuración.

Saludos,<br>
{{ config('variables.templateName') }}
</x-mail::message>
