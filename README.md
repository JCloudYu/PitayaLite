# Pitaya Lite Specification #

This framework is aimed to provide a intuitive mechanism for developers to rapidly provide web apis.

## Environmental Topology ##
The pitaya lite environment is organized as follows

```json
[ ROOT ]
	-> /gateway.php							<= main entry script
	-> /lib.php
	-> /pitaya.config.php					<= environmental configurations
	-> /pitaya/								<= framework root
	
	-> /ext/								<= extendable library path
	-> /app/								<= main services
		-> /app/boot.php					<= app dependent environmental configurations
		-> /app/{service}/{service}.php		<= apis provided by the constructed system
```


## Installation ##
This framework must cooperate with a web server. The framework is designed to process any incoming requests. Hence, the cooperated web server must redirect all requests into **gateway.php** to make the framework work.

### Apache ###
By default, the repository contains a .htaccess framework which is used in apache environment. Line 2 in **.htaccess** file redirects all requests into **gateway.php** and let the pitaya lite framework to do the rest for you.

### Other Web Servers ###
Developers must rewrite the requests to make the pitaya lite to handle all the requests and subrequests.



