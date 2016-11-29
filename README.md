Commando
========

Commando is a generic job-runner.

You can define your own commands in Json or Yaml files, and process jobs from different
stores such as a directory of files, or a database.

It's goal is to make it easy to implement background jobs into your application.

An example workflow:

1. You define a command "hello" in `commando.yml`:

```yml
commands:
  -
    name: hello
    template: echo "Hello {{ greeted }}"
    arguments:
      -
        name: greeted
        default: world
```
2. Your app writes a job into `/your/commando-path/new` called `1.json`:
```json
{
  "command": "hello",
  "arguments": {
    "greeted": "universe"
  }
}
```
3. You (or cron) runs `commando run`.
4. Commando will check for new job files in the configured store (in this case a directory), execute the command, replacing arguments in the template with the
passed job arguments, finally executing `echo "Hello universe"`
5. Commando reports the job status back to the store, including exit codes,
start/end times, total duration, and stdout and stderr
6. Your app reports back to the user if needed, based on the status report.

## Stores:

* `JsonDirJobStore`: Manages jobs through json files with a simple directory structure
* `PdoJobStore`: Manages jobs through a database table


## Use-cases

You can replace the `template` with much more complex command-lines or shell-scripts.

Your app should treat these jobs as "fire-and-forget", meaning the app
should not block, waiting for response. It's most ideal in scenarios where
your triggers commands and doesn't need to know about the response right away:

Examples:

* Update PDFs or reports in the background
* Send emails and notifications
* Create a database backup
* Process uploaded image files
* ...etc

## License

Please refer to the included LICENSE.md file

## Brought to you by the LinkORB Engineering team

<img src="http://www.linkorb.com/d/meta/tier1/images/linkorbengineering-logo.png" width="200px" /><br />
Check out our other projects at [engineering.linkorb.com](http://engineering.linkorb.com).

Btw, we're hiring!
