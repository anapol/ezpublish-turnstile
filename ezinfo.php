<?php
/**
 * Turnstile CAPTCHA extension for eZ Publish
 * Based on the reCAPTCHA extension by Bruce Morrison <bruce@stuffandcontent.com>
 * Adapted for Turnstile by [Your Name/Company]
 * Copyright (C) 2008. Bruce Morrison. All rights reserved.
 * Copyright (C) [Current Year] [Your Name/Company]. All rights reserved.
 * http://www.stuffandcontent.com
 * [Your Website]
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

class turnstileInfo
{
    static function info()
    {
        return array(
            'Name' => "Turnstile eZ Publish Integration",
            'Version' => "1.4",
            'Author' => "<a href='http://www.stuffandcontent.com'>Bruce Morrison</a> (Original reCAPTCHA), Adapted by [Your Name/Company]",
            'Copyright' => "Copyright (C) 2008-2011 Bruce Morrison, Copyright (C) [Current Year] [Your Name/Company]",
            'License' => "GNU General Public License v2.0",
        );
    }
}
?>
