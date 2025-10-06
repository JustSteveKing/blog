---
title: Sevalla on the Command Line
excerpt: Build a beautiful Go-powered CLI tool to manage your Sevalla apps directly from the terminal — no browser, just clean, fast, TUI driven control.
date: 2025-05-20
image: /prezet/img/ogimages/sevalla-on-the-command-line.webp
minRead: 7
sponsor: [Sevalla, "https://juststeveking.link/sevalla", "https://github.com/sevalla-hosting.png"]
author: steve
tags: [go, cli-development, terminal-ui, api-integration]
---

Sometimes you don't want to have to leave your terminal, it's easy, it's convenient, and it's always there. With that in mind, I thought I would write an article about how I like to check in on my applications that I have deployed on [Sevalla](https://juststeveking.link/sevalla).

Now, when building a CLI these days you have quite a few choices. You could go with a PHP application using Laravel Zero, a TypeScript application using a huge variety of options, if you're really nerdy (like me) you will want to use something you can compile and install _everywhere_. This is why when building CLI tools, I typically use GoLang.

CLI applications aren't what they used to be though, they used to be blinking cursors and text entry. These days we have entered the world of TUIs, Terminal User Interfaces. We have taken the terminal, and made it cleaner and prettier to work in.

I've previously worked with the Sevalla API in PHP, and TypeScript, but this is the first time working with it using Go. So, let's dive in an start a new Go CLI project and see what we can get put together.

Like all great projects, it needs a home, a directory, somewhere to live. So I usually use the `~/Developer` directory on my local machine, then organise it into websites, tools, etc etc. I will let you choose where you want yours to live, but I will keep mine in `~/Developer/GitHub/juststeveking` as I will likely publish it to GitHub shortly. Once you have found a home, give your project a name: `sevalla` and create the directory opening it up in your editor of choice.

From here on in, I will be referring to the project root directory which for me is `~/Developer/GitHub/juststeveking/sevalla` but this may be different for you.

Ok, let's kick this off with a `go mod init` to create our CLI as a module.

```shell
go mod init github.com/juststeveking/sevalla
```

You will notice that the directory path and name is similar enough, this is so I can navigate my local directories like I would on GitHub - a time saver when you work on multiple things all the time!

Let's create the following directories:
- `cmd`: A place for all individual commands to live.
- `internal`: A place for all internal code to live that the commands will need.

We are going to require a few dependencies for this project:
- [Cobra](https://github.com/spf13/cobra)
- [Clack Prompts](https://github.com/orochaa/go-clack/tree/master/prompts#readme)

Cobra allows us to build a well structured CLI application, and Clack Prompts will give us the terminal experience we want to build.

Let's install those:

```shell
go get -u github.com/spf13/cobra@latest
go get -u github.com/orochaa/go-clack/prompts@latest
```

Ok, our setup is pretty much complete. Let's move onto some actual code.

Create your `main.go` file, so we can start working with our CLI:

```go
package main

import "github.com/juststeveking/sevalla/cmd";

func main() {
	cmd.Execute()
}
```

All this needs to do is forward the operation to the `Execute` function in our `cmd` package. Let's take a look at this to start with.

```go
package cmd

import "github.com/spf13/cobra"

var rootCmd = &cobra.Command{
	Use: "sevalla",
	Short: "Sevalla is a command line tool for managing and interacting with your applications.",
	Run: func(cmd *cobra.Command, args []string) {
		cmd.Help()
	},
}

func Execute() {
	if err := rootCmd.Execute(); err != nil {
		panic(err)
	}
}
```

Our root command just wants to display the default help output, nice and simple. It tells our user what commands are available to them. You can test this now using: `go run main.go` in your project directory. You should see something like the following:

```shell
Sevalla is a command line tool for managing and interacting with your applications.

Usage:
  sevalla [flags]

Flags:
  -h, --help   help for sevalla
```

So far so good. Now, the main thing I want to do with my CLI tool, is to check on my deployed applications, so I am going to focus on this functionality. To do this though, I need two things. An API Key from Sevalla, and to know my Company ID that is on Sevalla too. These are things I would likely store locally and want the CLI to automatically pick up.

We get to our first command. An init command, where I want to create and save configuration for my CLI. There are a few schools of thought when it comes to storing config like this in your system, and I am not 100% sure if I have found the "right" way for me - but, I will use the `~/.config/sevalla` directory as I feel like it aligns better for me and how I typically configure my local system.

Our first internal bit of code. We want to have a package that will work with the config directory, file, and path. We can pretty much just call this `config` though.

```go
package config

import (
	"encoding/json"
	"os"
	"path/filepath"
)

type Config struct {
	ApiKey string `json:"api_key"`
	CompanyId string `json:"company_id"`
}

func New() *Config {
	return &Config{}
}

func (c *Config) SetApiKey(apiKey string) {
	c.ApiKey = apiKey
}

func (c *Config) SetCompanyId(companyId string) {
	c.CompanyId = companyId
}

func (c *Config) CreateConfig() error {
	configDir := filepath.Join(os.Getenv("HOME"), ".config", "sevalla")
	configFile := filepath.Join(configDir, "config.json")
	
	if err := os.MkdirAll(configDir, 0755); err != nil {
		return err
	}
	
	file, err := os.OpenFile(configFile, os.O_RDWR|os.O_CREATE|os.O_TRUNC, 0644)
	if err != nil {
		return err
	}
	
	defer file.Close()
	
	encoder := json.NewEncoder(file)
	if err := encoder.Encode(c); err != nil {
		return err
	}
	
	return nil
}
```

Now, that was a big chunk of code. Let's break it down a little.

```go
package config

import (
	"encoding/json"
	"os"
	"path/filepath"
)

type Config struct {
	ApiKey string `json:"api_key"`
	CompanyId string `json:"company_id"`
}

func New() *Config {
	return &Config{}
}
```

You need to define the package and imports, then create a struct for the data you want to store. I am using the `json:"api_key"` format so that the encoder will know how to handle data. Then, we have a `New` method which allows us to create a new Config struct to work with.

Next up, we have our setter methods, which will allow us to write to the config struct.

```go
func (c *Config) SetApiKey(apiKey string) {
	c.ApiKey = apiKey
}

func (c *Config) SetCompanyId(companyId string) {
	c.CompanyId = companyId
}
```

Finally, we have the creation/writing logic for the file and directory under the `CreateConfig` function, that may return an error. This can be accessed through the config, if you aren't familiar with go, so you can call `config.CreateConfig()`. Let's step through the logic in this function.

```go
configDir := filepath.Join(os.Getenv("HOME"), ".config", "sevalla")
configFile := filepath.Join(configDir, "config.json")
	
if err := os.MkdirAll(configDir, 0755); err != nil {
	return err
}
```

We define the config directory and file, then attempt to create them using the `os` package that's part of the standard library. If this fails for some reason, it will return the error allowing you to act accordingly.

```go
file, err := os.OpenFile(configFile, os.O_RDWR|os.O_CREATE|os.O_TRUNC, 0644)
if err != nil {
	return err
}
	
defer file.Close()
```

Next up, we try to create the file - deferring the closing of the file. Then if there is an error - again, return it.

Finally, we can serialise the data into JSON and write it to the file, returning any errors or `nil` if all went ok. In other languages this is like returning `void` and throwing an exception.

```go
encoder := json.NewEncoder(file)
if err := encoder.Encode(c); err != nil {
	return err
}
	
return nil
```

So, let's move onto the actual command for this.

```go
package cmd

import (
	"github.com/juststeveking/sevalla/internal/config"
	"github.com/orochaa/go-clack/prompts"
	"github.com/spf13/cobra"
)

var initCmd = &cobra.Command{
	Use: "init",
	Short: "Initialize the Sevalla CLI with your API Key and Company ID.",
	Long: `This command will create a configuration file in your home directory with the provided API Key and Company ID.`,
	Run: func(cmd *cobra.Command, args []string) {
		prompts.Intro("Initialize the Sevalla CLI with your API Key and Company ID.")
		// Create new config
		config := config.New()

		// ask for API Key and Company ID
		apiKey, err := prompts.Password(prompts.PasswordParams{
			Message: "Enter your API Key:",
		})

		if err != nil {
			panic(err)
		}

		companyId, err := prompts.Text(prompts.TextParams{
			Message: "Enter your Company ID:",
		})

		if err != nil {
			panic(err)
		}

		// set the API Key and Company ID
		config.SetApiKey(apiKey)
		config.SetCompanyId(companyId)

		// Create the config file
		if err := config.CreateConfig(); err != nil {
			panic(err)
		}

		prompts.Outro("Configuration file created successfully.")
	},
}
```

We start by displaying an intro, followed by prompting for some details. If any of these details fail for some reason, we will exit with the error. However, this should work, so we using the config package to write the API Key and Company ID, finally creating the configuration file and displaying an outro message.

Now our configuration is in place, we can start to actually talk to the Sevalla API and get a little information. I like to start here with a simple HTTP client, something that I can use in my implementation to streamline the logical code.

What we are building up to is a `Client` struct, that we are able to create and use.

```go
type Client struct {
	BaseURL string
	HttpClient *http.Client
	Headers map[string]string
}
```

We need the base URL, the actual HTTP Client, and the default headers. As usual, we start with a `new` function that will return the client for us.

```go
func New(baseUrl string, headers map[string]string) *Client {
	return &Client{
		BaseURL: baseUrl,
		Headers: headers,
		HttpClient: &http.Client{
			Timeout: 10 * time.Second,
		},
	}
}
```

At this point, we can create a client easily, and start interacting with the underlying client. Most of what I want to do right now is send `GET` requests, however support for other methods would be useful later. Let's create a `Send` function on our client we can use to send requests.

```go
func (c *Client) Send(method, path string, body interface{}) ([]byte, error) {
	var buf io.Reader

	if body != nil {
		b, err := json.Marshal(body)
		if err != nil {
			return nil, err
		}
		buf = bytes.NewBuffer(b)
	}

	req, err := http.NewRequest(method, fmt.Sprintf("%s%s", c.BaseURL, path), buf)
	if err != nil {
		return nil, err
	}

	for k, v := range c.Headers {
		req.Header.Set(k, v)
	}

	if body != nil {
		req.Header.Set("Content-Type", "application/json")
	}

	res, err := c.HttpClient.Do(req)
	if err != nil {
		return nil, err
	}

	defer res.Body.Close()

	if res.StatusCode >= 400 {
		return nil, fmt.Errorf("HTTP %d: %s", res.StatusCode, res.Status)
	}

	return io.ReadAll(res.Body)
}
```

This part is relatively straight forward. We need an IO Readers, and if the body isn't nil that we want to turn the body into a JSON byte buffer to send. We can then create a new request, and loop over the headers to add to the pending request we want to send. Finally, we send the request, deferring the close of the response body, if the status isn't successful we return the error, otherwise we return the contents of the response body.

Next, we need to actually use this in a `GET` or `POST` request.

```go
func (c *Client) Get(path string) ([]byte, error) {
	return c.Send(http.MethodGet, path, nil)
}
```

We can not easily send `GET` requests by proxying the call through to the `Send` function. Now that our basic implementation is in place, we can work on the more specific `Sevalla` code.

```go
package sevalla

import (
	"github.com/juststeveking/sevalla/internal/config"
	"github.com/juststeveking/sevalla/internal/http"
)

type Sevalla struct {
	Client *http.Client
	CompanyId string
}
```

Our `Sevalla` struct is the gateway at this point. Let's create a new instance.

```go
func New(config *config.Config) *Sevalla {
	return &Sevalla{
		Client: http.New("https://api.sevalla.com/v2", map[string]string{
			"Authorization": "Bearer " + config.ApiKey,
		}),
		CompanyId: config.CompanyId,
	}
}
```

We want to return our `Sevalla` struct, creating a new Http Client, passing through the Base URL and headers, including the `Authorization` header. Then the company ID from the config too. This gives us everything we need to get started programatically sending the API requests for our Sevalla account.

To get a list of applications from the API we can use the following:

```go
func (s *Sevalla) Applications() ([]byte, error) {
	return s.Client.Send(
		"GET",
		"/applications",
		nil,
	)
}
```

Let's now start building this CLI command.

```go
package cmd

import "github.com/spf13/cobra"

var appListCmd = &cobra.Command{
	Use: "apps:list",
	Short: "List all applications running in your company account on Sevalla.",
	Run: func(cmd *cobra.Command, args []string) {
		// Logic will go here.
	},
}
```


We need to make sure we register these commands in our `cmd/root.go` root command file. We can register sub-commands for our CLI using the following approach:

```go
func init() {
	rootCmd.AddCommand(initCmd)
	rootCmd.AddCommand(appListCmd)
}
```

We are telling our CLI that the root command contains the `initCmd` and the `appListCmd`. Let's now work on actually getting our list of applications. We need to update the `Sevalla.Applications` function and add some additional types.

```go
type Application struct {
	ID string `json:"id"`
	Name string `json:"name"`
	DisplayName string `json:"display_name"`
	Status string `json:"status"`
}

type applicationsResponse struct {
	Company struct {
		Apps struct {
			Items []Application `json:"items"`
		} `json:"apps"`
	} `json:"company"`
}

func (s *Sevalla) Applications() ([]Application, error) {
	data, err := s.Client.Send(
		"GET",
		"/applications"+"?company="+s.CompanyId,
		nil,
	)

	if err != nil {
		return nil, err
	}

	var resp applicationsResponse
	if err := json.Unmarshal(data, &resp); err != nil {
		return nil, err
	}

	return resp.Company.Apps.Items, nil
}
```


We are now able to use this module to make the request.

```go
var appListCmd = &cobra.Command{
	Use: "apps:list",
	Short: "List all applications running in your company account on Sevalla.",
	Run: func(cmd *cobra.Command, args []string) {
		c := config.New()

		if err := c.Load(); err != nil {
			panic(err)
		}

		sevalla := sevalla.New(c)
		apps, err := sevalla.Applications()
		if err != nil {
			panic(err)
		}

		for _, app := range apps {
			fmt.Printf("ID: %s, Name: %s, Display Name: %s, Status: %s\n", app.ID, app.Name, app.DisplayName, app.Status)
		}
	},
}
```

We can get a nicer output for this though right! Let's try and refactor where we are looping over our apps. For this we can use a `prompts.Note` which will allow us to pass a title, message, and have borders on the output.

```go
for _, app := range apps {
	prompts.Note(
		fmt.Sprintf("ID: %s Status: %s\n\n", app.ID, app.Status),
		prompts.NoteOptions{
			Title: app.DisplayName,
		},
	),
}
```

Finally, let's make the output stand out a little more by outlining the ID and status and giving it a different color.

```go
fmt.Sprintf(
	"ID: %s Status: %s\n\n",
	picocolors.Underline(picocolors.Cyan(app.ID)),
	picocolors.Underline(picocolors.Cyan(app.Status)),
),
```

There we have it, a nice and easy to use CLI tool, that we can use to quickly check on our applications we have hosted on Sevalla! All we have to run is `sevalla apps:list` and we will get a nice clean output that's easy to read and understand.

If you want to take this further, I would investigate any of the packages by [CharmCLI](https://charm.sh/) who make _the_ best TUI libraries for Go.
