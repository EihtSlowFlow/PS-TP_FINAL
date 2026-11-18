<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Contact data of the agency shown on the public pages (landing, HU-28; property detail, HU-29).
 *
 * They live here, and not in the views, so the real data are changed in one place. They can also be overridden from
 * the `.env` file, e.g. `agency.phone = '+54 (299) 000-0000'`.
 *
 * The values below are placeholders until the team confirms the real ones.
 */
class Agency extends BaseConfig
{
    public string $address = 'Av. Alem 1250, Cipolletti, Río Negro';

    public string $phone = '+54 (299) 477-8899';

    public string $email = 'contacto@inmogestion2026.com.ar';

    public string $hours = 'Lunes a Viernes de 9:00 a 18:00 hs';

    /** International format, digits only (it is used to build the https://wa.me/ link). */
    public string $whatsapp = '5492994778899';
}
