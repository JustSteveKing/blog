---
title: Deploying Laravel on Sevalla - A No-Nonsense Guide
excerpt: Deploy your Laravel app with Sevalla using Docker & FrankenPHP for seamless, zero-downtime releases. Say goodbye to server hassles and Kubernetes headaches!
date: 2025-02-10
image: "/images/articles/deploying-laravel-on-sevalla.png"
minRead: 14
category: infrastructure
sponsor:
  name: Sevalla
  url: https://juststeveking.link/sevalla
  logo: https://github.com/sevalla-hosting.png
author: steve
tags: ["laravel", "sevalla", "docker", "cloud-deployment"]
---

There are many different ways you can deploy a Laravel application, from creating a VPS and setting it all up yourself, to configuring a kubernettes cluster and deploying into it, to signing up for something like Laravel Forge and letting them to do all the hard work for you. These options aren't great, you either have to manage the server yourself, or understand kubernettes which is a minefield in itself.

There is another option though, to use a platform as a service - otherwise known as PaaS. These offer a middleground between hands off and hands on, they also don't usually require in-depth knowledge of kubernettes either. While there are a number of options out there when it comes to modern PaaS platforms, one that really stands out to me as a developer is [Sevalla](https://juststeveking.link/sevalla).

Sevalla isn't just a new kid on the block, or some dude creating a hosting service to resell from his apartment. They've actually been around for a number of years already offering managed WordPress hosting previously, which makes them uniquely position for understanding how to help you host your next web application.

What I wanted to do in this article, is to walk through how you can go from a standard Laravel application to a deployed Laravel application using Sevalla. There are a couple of different ways that you can actually deploy a Laravel application on Sevalla, either allowing Sevalla to figure it out for you as part of the configuration step, or you can provide a docker configuration and Sevalla will run this container for you.

I will be providing a Docker image for this article, as when you host a Laravel application you really do want to use something like Laravel Octane and FrankenPHP. It was an absolute game changer for the PHP community when it was released.

If you want a good example of how to do this, the Sevalla team have created a Laravel template that is open source on their [GitHub](https://github.com/sevalla-templates/laravel-demo). If you have a look at the repo, you will notice that it has a Dockerfile in the root of the project. Let's take a look at this file a second:

```yaml
FROM dunglas/frankenphp:php8.3-bookworm
ENV SERVER_NAME=":8080"
RUN install-php-extensions @composer
WORKDIR /app
COPY . .
RUN composer install \
  --ignore-platform-reqs \
  --optimize-autoloader \
  --prefer-dist \
  --no-interaction \
  --no-progress \
  --no-scripts

So, we are grabbing FrankenPHP for PHP 8.3 and binding port `8080` to the `SERVER_NAME` env variable. We then want to install composer, set the working directory, copy the project across, then install the dependencies. This is a pretty clean and simple setup, the only thing I'd possibly change is the PHP version!

Honestly, that is all it takes. You don't need to worry about the database layer, or storage later etc - what we want to focus on here is the application itself. Sevalla has the concept of Applications and Databases, you create and manage these separately. Each application that you deploy can have multiple processes, and by default will deploy with a web process. Alongside this web process, you can also set up a Background Worker Process, Cron Process, and a Job Process.

category: infrastructure
- **Cron**: this is a process that runs just like CRON does on a typical server, it works in much the same way.
- **Job**: this process allows you to run a task in the background once, which is useful for alerts, notifications, migrations, etc.
Instead of just talking about what Sevalla can do, why don't we actually deploy something to see how it works and how intuitive it is?

So, like most developers, I am constantly coming up with ideas for how I'd like to build my personal website. You know, the perfectly acceptable one you are visiting right now. Well, currently it is built using Astro and Svelte which is pretty awesome, but tbh - I have been wanting to build it in Laravel for such a long time. No real reason why, other than I like PHP and Laravel is modern PHP. Sevalla supports Laravel and PHP really well, it's almost effortless.

Before we get to deploying the application, let's talk through what this website is going to contain. What is going to make this website work, so we can understand how we deploying it.

As of writing this, Laravel 12 is due to be released in a number of weeks. So when kicking this project off, I used the `--dev` flag to get the latest and greatest of the framework I love.  I decided I didn't want to use a starter kit for this project, as I don't need authentication or anything like that - it's a relatively flat and basic website. However, I will likely add a filament admin panel at some point to allow me to save markdown content for my articles.

It's a pretty typical Laravel application, nothing special going on. It has a database, and that is about it! Super simple right? So, how can we go about deploying this on Sevalla? Let's break it down, step by step.

I have my Laravel application, I am using GitHub for version control. Once I have pushed my latest changes up to GitHub,  all I need to do is connect it to Sevalla and they will take care pretty much all the rest. It is a very hands off deployment process, that enables zero downtime deployments, and auto-scale magic. 

You select the repository, or paste in the URL depending if it is a public or private repo. Choose the branch, and give the application a name. Then you can choose a region, as Sevalla is backed by Google Cloud Platform the available regions are quite good! Then, it is time to choose resources. There are quite a few different choices when it comes to resource allocation, from the smallest $5 a month instance that has 0.3 CPUs and  0.3 Gb of memory - all the way up to $480 beast with 8 CPUs and 32 Gb of memory. 

As I mentioned before, this is a simple website. It isn't doing a lot of memory intensive operations, and it definitely isn't going to require much processing power. So, I will go for a quick $5 instance that will do the job of serving my articles. I can scale this up should I see a spike in resource usage, as that will take me minutes at most.

I'd recommend selecting "create" next, instead of "create and deploy" as we still need to set up our environment and database connection - no point deploying something half done right! This will take us to a screen which I'd call the "environment canvas" which explains very visually the state of your environment. You get free DDoS protection, and the ability to enable edge-caching and CDN usage from Cloudflare as standard. Then you can see the details of your soon to be deployed application, with a quick settings button to quickly scale and update how your instance is configured.

It's like using kubernettes, without any headaches, without any infrastructure worries, all for a transparent price that is clear what you are getting and what it is going to cost you. Predictable.

From here, we can navigate over to our environment variables and either add them manually, or simply import from our `.env` file - which is definitely a time saver! Once these are in, you can tweak, edit, and manage these env variables as much as you need to before deploying your application. Again, don't deploy it just yet - the default Laravel session driver is the database, so your app just won't work properly yet.

Next, if we return to our company dashboard in Sevalla, we can go to create a new database. You get the usual options of MySQL,  PostgreSql, MariaDB, etc. I will choose Postgres for this, as it is my preferred database 80% of the time. You can choose the version, set the database name,  and then configure the credentials. Sevalla allows you to connect services/applications to this database with the click of a button - even allowing you to add the env variables automatically saving potential copy paste errors or issues.

At this point, we can deploy. Hit that button, I dare you. With the click of a button and a short wait later, we have a deployed Laravel application that is going to automatically deploy anytime we push or merge into the main branch of our repo. Giving us hassle free, zero downtime deployments, that are able to leverage all the goodness of Cloudflare and Kubernettes without having to actually use them directly. The first deployment will be a little slower than subsequent deployments, simply because it is the first time you are creating the application and its required dependencies. 

But, we have a problem. We have not migrated our database yet! Not to worry, click on over to the web terminal, and run them yourself! You can of course add this as part of your "build" command, but that is up to you. It isn't always recommended to run migrations on deploy, depending on what you're building etc etc. But I won't go down that rabbit hole right now.

There we have it, we have managed to deploy our first Laravel application onto Sevalla and it was pretty painless and easy. We could take it further and customise the runtime, leveraging Laravel Octane and FrankenPHP - but I will leave that for you to figure out, or perhaps a future adventure?
