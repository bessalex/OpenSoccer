# OpenSoccer

Online Soccer Manager

**Live demo:** [www.opensoccer.org](http://www.opensoccer.org/)

## Setup

 1. Put the PHP files up on a web server
 2. Add the two subdomains `www` and `m` for your domain
 2. Set up an empty MySQL database with collation `utf8_general_ci` and privileges `SELECT, INSERT, UPDATE, DELETE, DROP`
 3. Run the SQL from [Database/STRUCTURE.sql](Database/STRUCTURE.sql) to create the database structure
 4. Run the SQL from [Database/DATA.sql](Database/DATA.sql) to add the initial data for the game
 5. Edit [Website/config.example.php](Website/config.example.php) so that it matches your installation and rename it to `Website/config.php`
 6. Set up all the cron jobs listed below
 7. Change the password for the default user with administrator rights (username: `Admin`, password: `admin`)
 8. Make sure that [GNU gettext](http://php.net/manual/de/book.gettext.php) is installed, e.g. on Ubuntu via

    ```
	sudo apt-get install gettext
	apt-get install locales
	```

 9. Make sure that the [Intl extension](http://php.net/manual/de/book.intl.php) is installed, e.g. on Ubuntu via

    `sudo apt-get install php5-intl`

 10. Make sure the directory `cache` is writable

## Cron jobs

 * [Website/aa_buffer_reservas.php](Website/aa_buffer_reservas.php): every 10 minutes; except for hours 10-11, 14-15, 18-19 and 22-23
 * [Website/aa_gestion_ordenador.php](Website/aa_gestion_ordenador.php): every 10 minutes
 * [Website/aa_sorteo_copa_int.php](Website/aa_sorteo_copa_int.php): every 30 minutes
 * [Website/aa_analisis_bd.php](Website/aa_analisis_bd.php): every day
 * [Website/aa_despidos.php](Website/aa_despidos.php): every hour
 * [Website/aa_cobro_salarios.php](Website/aa_cobro_salarios.php): every day
 * [Website/aa_loteria.php](Website/aa_loteria.php): every day
 * [Website/aa_calcular_valor_mercado.php](Website/aa_calcular_valor_mercado.php): every 5 minutes
 * [Website/aa_deteccion_multi.php](Website/aa_deteccion_multi.php): every 5 minutes
 * [Website/aa_mercado_transferencias_npc.php](Website/aa_mercado_transferencias_npc.php): every 15 minutes
 * [Website/aa_sorteo_copa.php](Website/aa_sorteo_copa.php): every 6 hours
 * [Website/aa_liquidacion_primas.php](Website/aa_liquidacion_primas.php): every 15 minutes
 * [Website/aa_fin_temporada.php](Website/aa_fin_temporada.php): every day; at hour 22
 * [Website/aa_crear_jugadores.php](Website/aa_crear_jugadores.php): every 30 minutes
 * [Website/aa_mejora_jugadores.php](Website/aa_mejora_jugadores.php): every 15 minutes
 * [Website/aa_crear_calendario.php](Website/aa_crear_calendario.php): every day; at hour 23
 * [Website/aa_simulacion_jornada.php](Website/aa_simulacion_jornada.php): every minute; at hours 10-11, 14-15, 18-19 and 22-23
 * [Website/aa_costes_estadio.php](Website/aa_costes_estadio.php): every day; at hour 23
 * [Website/aa_calcular_tablas.php](Website/aa_calcular_tablas.php): every 2 minutes; at hours 16-17
 * [Website/aa_calcular_fuerza_equipo.php](Website/aa_calcular_fuerza_equipo.php): every 5 minutes
 * [Website/aa_ingresos_tv.php](Website/aa_ingresos_tv.php): every 6 hours

## Contributing

Any contributions are welcome :) Please fork this repository, apply your changes, and submit your contributions by sending a pull request.

## Translating

In order to provide translations for this project, please refer to our [documentation](https://github.com/delight-im/PHP-I18N) and find the translation files in [Website/i18n](Website/i18n).

### Custom Poedit settings

 * Go to `File` - `Preferences` - `Parsers` - `PHP` - `Edit`. In the list of extensions, add `;*.php.txt` at the end.
 * Go to `File` - `Preferences` - `Translation Memory`. Disable the checkbox for `Use translation memory`.
 * Go to `Catalogue` - `Properties` - `Sources keywords`. Add a new entry `__` (double underscore).

## License

All parts of this project, except for the folder `Website/images`, have been released under the following license:

```
 Copyright (c) delight.im <info@delight.im>
 
 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.
 
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 
 You should have received a copy of the GNU General Public License
 along with this program.  If not, see {http://www.gnu.org/licenses/}.
```