

The repo now includes configuration updates that allow Xdebug to work with VS Code in the portlandor project. The first time you use a branch with this extended config, you will need to rebuild your containers using the "lando rebuild" command.

Windows Prerequisites:

1. PHP must be installed locally on the host machine, even if you are not running it under Windows. The preferred location is C:\PHP.

VS Code Config

1. Open Workspace Settings (.vscode/settings.json) and add the PHP executable path. Your local version of PHP may be in a different directory, such as C:\PHP7.

	{
	    "php.validate.executablePath": "C:\\PHP\\php.exe"
	}

2. Update the debug launch file (.vscode/launch.json) and update with the following settings. Update the paramter "pathMappings" to match the path to the project directory on your local machine.

	{
	    "version": "0.2.0",
	    "configurations": [
		{
		    "name": "Listen for XDebug",
		    "type": "php",
		    "request": "launch",
		    "port": 9000,
		    "log": true,
		    "pathMappings": {
			"/app/": "c:/_projects/portlandor/",
		    }
		},
		{
		    "name": "Launch currently open script",
		    "type": "php",
		    "request": "launch",
		    "program": "${file}",
		    "cwd": "${fileDirname}",
		    "port": 9000
		}
	    ]
	}

You should now be able to set and hit a breakpoint when debugging in VS Code.

== Troubleshooting ==

If breakpoints aren't being hit, here are some things to check.

1. The file /php.ini contains a hard-coded IP address for the xdebug.remote_host parameter. In Windows, this value can be found by running the cmd "ipconfig /all" in a cmd window. Look for the ethernet adapter labeled DockerNAT and copy the IPv4 address and verify this value matches what's in php.ini. The value is typically 10.0.75.1.



