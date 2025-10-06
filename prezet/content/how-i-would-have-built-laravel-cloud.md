---
title: How I Would Have Built Laravel Cloud
excerpt: Discover an alternative approach to how I would have built Laravel Cloud, exploring platform architecture, developer experience, and the balance between simplicity and flexibility.
date: 2025-09-16
image: /prezet/img/ogimages/how-i-would-have-built-laravel-cloud.webp
minRead: 12
category: infrastructure
author: steve
tags: [laravel, cloud-platforms, devops, platform-architecture]
---

## Introduction

The Laravel team has done something remarkable with Laravel Cloud. In what feels like record time, they've built a platform that makes deploying Laravel applications as simple as pushing to Git. Recently, they've been generous enough to share the technical details of how they built it - piece by piece, decision by decision. These kinds of behind-the-scenes articles are gold for developers like us because they give us a rare glimpse into how experienced teams solve complex problems.

But here's the thing: every architectural decision comes with trade-offs, and every team brings their own perspective to the table. While I have nothing but respect for what the Laravel team has accomplished, I found myself thinking, "How would I have approached this differently?" Not because I think I could build it better - that's not the point. The real value comes from understanding how different developers tackle the same problems, because that's how we all get better at this craft.

Laravel Cloud's technical story is worth discussing not just for what it achieved, but for the conversations it can spark about platform architecture, developer experience, and the eternal tension between simplicity and flexibility.

## Laravel Cloud's Architecture: Strengths and Choices

Let's start by acknowledging what Laravel Cloud gets right. The team built their platform on AWS and Kubernetes - a choice that makes perfect sense given their history with Laravel Vapor. They know AWS inside and out, they understand its quirks, and they've built the operational knowledge to run services reliably on it.

Their approach centers around taking Laravel applications, building them into Docker images using a specific set of preconfigured images, and deploying them to a Kubernetes cluster. This gives them a few key advantages:

**Predictable Environment**: When you control the entire stack from the base image up, you eliminate a huge class of "it works on my machine" problems. The Laravel team knows exactly what's running in production because they control every layer.

**Optimized for Laravel**: The preconfigured images come with everything a typical Laravel application needs - the right PHP version, essential extensions, web server configuration, and all the bits that make Laravel sing.

**Support Simplicity**: When something goes wrong, the Laravel team doesn't have to debug an infinite variety of custom setups. They know the stack intimately, which means faster resolution times and better support experiences.

This is smart platform design. By constraining choices, they've reduced complexity and created a deployment experience that "just works" for the majority of Laravel applications.

## Where the Limitations Start

But here's where things get interesting - and where my perspective starts to diverge. That same constraint that makes Laravel Cloud reliable also makes it limiting. Want to install a specific PHP extension for image processing? Not supported. Prefer Caddy over Nginx for your specific use case? Sorry, that's not an option. Need to run a Node.js micro-service alongside your Laravel app? You're out of luck.

These aren't criticisms of Laravel Cloud's design philosophy - they're the natural consequences of their approach. The Laravel team made a conscious choice to optimise for the 80% case, accepting that they'd lose some flexibility for the 20% of users with edge cases.

I totally get why they made this choice. Building a platform that supports every possible configuration is a nightmare. You end up with the complexity of something like AWS itself, where you need a PhD in cloud architecture just to deploy a simple application.

But as someone who's worked with teams that regularly bump up against these kinds of limitations, I can't help but wonder: what if we could have both simplicity and flexibility?

## The Case for Google Cloud's Ecosystem

This is where Google Cloud's approach to platform services enters the picture - and honestly, it's what I would have reached for if I were building Laravel Cloud from scratch.

Google Cloud gives you something beautiful: a spectrum of services that let you choose your own complexity level. You can start with fully managed solutions like App Engine for zero-configuration deployments, then gradually move to more flexible options like Compute Engine or Google Kubernetes Engine as your needs evolve.

Think about what this means for a Laravel Cloud-style platform:

**Complete Control When Needed**: Want to use a custom PHP extension? Deploy to Compute Engine with your own Docker setup. Prefer a different web server? You have the flexibility. Need to run background workers with specific dependencies? Google Cloud's container services have you covered.

**Managed Services Where They Make Sense**: Don't want to manage databases? Cloud SQL handles that. Need file storage? Cloud Storage is there. Want to add AI capabilities? The ML and AI services integrate seamlessly.

**Scaling Philosophy**: Start simple with App Engine, then scale to Container Registry with Kubernetes Engine, or even go serverless with Cloud Functions for specific workloads.

Here's what a flexible deployment architecture might look like:

```yaml
FROM php:8.4-fpm-alpine

# Install exactly what YOU need
RUN docker-php-ext-install pdo_mysql imagick redis

# Copy your application
COPY . /var/www/html

# Your custom configuration
COPY nginx.conf /etc/nginx/nginx.conf

EXPOSE 8080
CMD ["php-fpm"]
```

Deploy that container to Google Kubernetes Engine, connect it to Cloud SQL for your database, use Cloud Storage for assets, and you're running exactly the stack you designed - not someone else's interpretation of what you should need.

## Sevalla: My Blueprint for a Laravel Cloud Alternative

When I think about how I'd actually implement this vision, I keep coming back to what the team at Sevalla has built. Their platform is essentially my answer to "what would Laravel Cloud look like if you started with Google Cloud's flexible service ecosystem instead of AWS constraints?"

Sevalla's architecture hits that sweet spot I was talking about. They've built their platform on Google Cloud services and Cloudflare, which gives you:

**Infrastructure That Scales With You**: Whether you need the simplicity of fully managed services or the control of custom infrastructure, Google Cloud's service ecosystem adapts to your requirements. Start with managed databases and scale to custom compute configurations when needed.

**Developer-Centric Experience**: You get unlimited staging environments, straightforward pricing without surprise bills, and a dashboard that actually makes sense. No need to become a cloud architecture expert just to deploy your app.

**Freedom to Build**: Since you control your infrastructure choices, you can deploy anything. Laravel with custom extensions? Check. Node.js API? Check. Go microservice? Check. Static site with custom build process? Absolutely.

Here's what I love about this approach: it doesn't force you to choose between simplicity and flexibility. You get both.

While Sevalla defaults to Nixpacks for automatic builds, they actually recommend using Docker for more control and flexibility. As their documentation puts it: "Using a Dockerfile gives you more control, and you can use almost any language, so you are not restricted to the languages Nixpacks or Buildpacks support."

Their official Laravel template demonstrates this approach with a clean Dockerfile:

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
```

Environment variables, regions, and deployment settings are handled through Sevalla's dashboard. No complex Kubernetes manifests, no AWS-specific configuration, just straightforward Docker containers with the freedom to customize exactly what you need, backed by Google Cloud's robust infrastructure services.

## Laravel Cloud vs. Sevalla: A Direct Comparison

Let me be clear: both platforms are solving the same core problem - making deployment simple for developers. Where they differ is in their approach to the flexibility-simplicity trade-off.

**Laravel Cloud's Philosophy**: "We'll give you a curated, optimized experience for Laravel applications. Trust us to make the right choices for you."

**Sevalla's Philosophy**: "We'll give you the tools to make your own choices, but handle all the infrastructure complexity for you."

Both approaches have merit. Laravel Cloud is probably the better choice if you're running a standard Laravel application and want zero configuration. You literally just push your code and it works.

But if you're the kind of developer who wants to optimize your stack, experiment with new tools, or deploy non-Laravel workloads alongside your main application, Sevalla's approach gives you that freedom without sacrificing simplicity.

Here's a practical example: let's say you want to add real-time features to your Laravel app using a Node.js service for WebSocket handling.

**With Laravel Cloud**: You'd need to use a separate service for the Node.js component, manage the integration between services, and handle the complexity of multiple deployment pipelines.

**With Sevalla**: You could build a multi-container setup or deploy the Node.js service as a separate containerized instance on Google Cloud's infrastructure, all managed through the same platform with the same tooling.

The difference is subtle but important: one platform makes assumptions about what you want to build, while the other gives you the tools to build whatever you envision.

## Why Platform Choice Shapes Everything

Here's what I've learned after years of building and deploying applications: the platform you choose shapes not just how you deploy, but how you think about architecture, scaling, and problem-solving.

When you're constrained to a specific stack, you start architecting around those constraints. You make design decisions based on what's possible within the platform's limitations, rather than what's optimal for your specific use case.

When you have more flexibility, you can optimize for your actual requirements. Need a specific caching strategy? Build it. Want to experiment with edge computing? Go for it. Have a unique performance requirement? You can address it directly.

This isn't to say that constraints are bad - they can be incredibly valuable for reducing decision fatigue and ensuring best practices. But the key is choosing your constraints consciously, rather than having them imposed by your platform.

## My Technical Implementation Approach

If I were actually building this platform, here's how I'd architect it:

**Core Infrastructure**: Google Cloud's containerized services for application hosting, Cloud SQL for managed databases, Cloud Storage for assets, and Cloudflare for edge services and security. The beauty is you can start with Google App Engine for simplicity and migrate to Google Kubernetes Engine for more control without changing your core application architecture.

**Developer Experience**: Git-based deployments with automatic builds, preview environments for every branch, and a CLI tool for local development that mirrors the production environment.

**Container Strategy**: Provide base images for common stacks (Laravel, Node.js, etc.) but allow complete customization through Dockerfile support or Nixpacks configuration. Deploy to the Google Cloud service that best fits your requirements - whether that's the simplicity of App Engine or the flexibility of custom Compute Engine instances.

The actual deployment flow would be through Sevalla's dashboard - you connect your GitHub repository, configure your build settings, and deployments happen automatically on push. Preview environments get created for pull requests without any additional CLI tools needed.

**Scaling Philosophy**: Start with sensible defaults but expose all the knobs when needed. Most applications don't need custom scaling policies, but when they do, they really need them. Google Cloud's service ecosystem lets you scale both up and out as requirements change.

## Learning from Both Approaches

What's fascinating to me is that both Laravel Cloud and Sevalla represent valid solutions to the same fundamental problem. The Laravel team optimized for their specific community and use case, while Sevalla optimized for flexibility and choice.

As developers, we benefit from understanding both approaches. Sometimes you need the opinionated simplicity of Laravel Cloud. Other times, you need the flexibility of a platform like Sevalla that leverages Google Cloud's diverse service ecosystem. The key is recognizing which situation you're in and choosing accordingly.

I've worked on projects where Laravel Cloud's constraints would have been perfect - standard Laravel applications where the team just wanted to focus on business logic rather than infrastructure. I've also worked on projects where those same constraints would have been dealbreakers - applications with specific performance requirements, custom tech stacks, or unique architectural needs.

## Conclusion

The Laravel team deserves enormous credit for what they've built with Laravel Cloud. They've created a platform that makes deployment genuinely simple for Laravel developers, and they've done it with the kind of polish and attention to detail that we've come to expect from the Laravel ecosystem.

My alternative approach - building on Google Cloud's flexible service ecosystem with platforms like Sevalla - isn't better or worse, it's just different. It optimises for different trade-offs and serves different needs.

What I hope this exploration has done is give you a different lens through which to think about platform architecture and developer experience. The next time you're evaluating deployment options or building your own tools, consider not just what works, but how different approaches shape the way you think about problems.

The beauty of our industry is that there's rarely one "right" answer. There are just different perspectives, different trade-offs, and different optimizations for different contexts. The more we understand these different approaches, the better equipped we are to make the right choice for our specific situation.

So whether you choose Laravel Cloud, Sevalla, or build your own solution entirely, make that choice consciously. Understand the trade-offs, embrace the constraints that serve you, and push back against the ones that don't.

Because at the end of the day, the best platform is the one that gets out of your way and lets you build amazing things.
