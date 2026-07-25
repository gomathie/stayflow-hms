<div align="center">
	<p>
		<b>StayFlow - An open source and free platform to launch your own hotel booking website</b>
	</p>
</div>

<p align="center">
	<a href="/LICENSE.md"><img src="https://img.shields.io/badge/License-OSL%20V3-green" alt="License"></a>
</p>

## Topics
- [Topics](#topics)
	- [Introduction](#introduction)
	- [Requirements](#requirements)
		- [Hosted Server Configurations](#hosted-server-configurations)
		- [Local Server Configurations](#local-server-configurations)
	- [Installation and Configuration](#installation-and-configuration)
	- [License](#license)
	- [Security Vulnerabilities](#security-vulnerabilities)
	- [Contribute](#contribute)


### Introduction

StayFlow is a true open-source hotel reservation system and a booking engine. The system is dedicated to channeling the power of the open-source community to serve the hospitality industry.

From small independent hotels to big hotel chains, StayFlow is a one-stop solution for all your hotel business needs.

You will be able to launch your hotel website, showcase your property and take and manage bookings.

### Requirements

In order to install StayFlow you will need the following server configurations for hosted and local servers.
The system compatibility will also be checked by the system with installation and if the server is not compatible then the installation will not move ahead.

#### Hosted Server Configurations

* **Web server**: Apache 1.3, Apache 2.x, Nginx or Microsoft IIS
* **PHP  version**: PHP 8.1+ to PHP 8.4
* **MySQL version**:  5.7+ to 8.4 installed with a database created
* SSH or FTP access (ask your hosting service for your credentials)
* In the PHP configuration ask your provider to set memory_limit to "128M", upload_max_filesize to "16M" ,    max_execution_time to "500" and allow_url_fopen "on"
* SSL certificate if you plan to process payments internally (not using PayPal for instance)
* **Required PHP extensions**: PDO_MySQL, cURL, OpenSSL, SOAP, GD, SimpleXML, DOM, Zip, Phar

#### Local Server Configurations

* **Supported operating system**: Windows, Mac, and Linux
* **A prepared package**: WampServer (for Windows), Xampp (for Windows and Mac) or EasyPHP (for Windows)
* **Web server**: Apache 1.3, Apache 2.x, Nginx or Microsoft IIS
* **PHP**: PHP 8.1+ to PHP 8.4
* **MySQL** 5.7+ to 8.4 installed with a database created
* In the PHP configuration, set memory_limit to "128M", upload_max_filesize to "16M" and max_execution_time to "500"
* **Required PHP extensions**: PDO_MySQL, cURL, OpenSSL, SOAP, GD, SimpleXML, DOM, Zip, Phar

### Installation and Configuration

Follow the standard installation steps for your server environment to set up StayFlow's database and configuration files.

### License

StayFlow Core is licensed under OSL-3.0 and modules have their applicable license, LICENSE.md, kept inside their root directories, while other modules are licensed under AFL-3.0.

The online copy of OSL-3.0 can be found at [https://opensource.org/licenses/OSL-3.0](https://opensource.org/licenses/OSL-3.0).

The online copy of AFL-3.0 can be found at [https://opensource.org/licenses/AFL-3.0](https://opensource.org/licenses/AFL-3.0).

### Security Vulnerabilities

Please don't disclose security vulnerabilities publicly.

### Contribute

As a PHP developer who has command on PHP and MySQL and also knows how to use Git or GitHub efficiently, can contribute to code enhancements via pull requests.<br>
For more information about the contribution process please check **[Contribute to StayFlow](/CONTRIBUTING.md)**
</content>
