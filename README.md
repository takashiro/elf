Elf Web App
==========

| Example Page | http://weifruit.cn   |
|--------------|----------------------|
| Author       | Kazuichi Takashiro   |


Lisense
-------
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.

Introduction
------------

Elf is an extensible raw web application.

Features
----------

* Error Reporter, record each run-time error as a report in data/error/
* File-based data storage and cache
* Simple but powerful template engine with statements like if, elseif, else, loop, echo and eval.
* Frequently-used input elements like select, radio and checkbox can be output easily without foreach or many if statements
* MySQL connection class
* Automatically attached and removed cookie prefix to avoid cookie collision
* Alipay via mobile webpage supported
* Hook engine, for open-close principle
* Extra functional modules can be easily added

Running Environment
-------------------
1. PHP 5.5
2. MySQL 5
3. Apache Or IIS
4. PHP extension: cURL, openssl, mcrypt

Run http://yoursitename/install and it will be working.
