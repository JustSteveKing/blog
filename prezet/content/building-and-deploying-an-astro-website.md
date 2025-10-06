---
title: Building and Deploying an Astro Website - A Deep Dive
excerpt: Learn how I built my personal dashboard using Astro, integrating dynamic content and productivity features, and deployed it with Sevalla and Cloudflare.
date: 2025-06-19
image: /prezet/img/ogimages/building-and-deploying-an-astro-website.webp
minRead: 5
category: frontend
sponsor: [Sevalla, "https://juststeveking.link/sevalla", "https://github.com/sevalla-hosting.png"]
author: steve
tags: [astro, web-development, personal-dashboard, deployment]
---

<div class="w-full py-4">
  <div class="aspect-video rounded-lg overflow-hidden shadow-lg">
    <iframe
      src="https://www.youtube.com/embed/OEDoHrS6ogo?si=nQniS3J2vtx_oFRR"
      title="YouTube video"
      allowfullscreen
      class="w-full h-full"
      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
    ></iframe>
  </div>
</div>

Recently, I decided it was time to give my online presence a refresh. While my main website, built with Nuxt.js, was working great, I wanted a dedicated space to share real-time updates about what I'm working on—a kind of digital “now” page. This led me to create now.juststeveking.com, a personal dashboard that offers a transparent, read-only view into my daily work, tools, and goals. In this article, I'll walk you through my process for building and deploying this site using Astro, integrating dynamic content, and adding productivity features, all the way through to deployment with Sevalla and Cloudflare.

## Why a Separate 'Now' Page?

My motivation was simple: I wanted a space distinct from my main website that could serve as a living snapshot of my current projects, tools, and goals. My previous site had a "now" section, but I envisioned something more interactive and visually engaging—a dashboard that could evolve with me. Astro, with its simplicity and flexibility, felt like the perfect fit, especially since it allowed me to incorporate React components where needed.

## Setting Up Astro and Tailwind CSS

I kicked things off by initializing a new Astro project. Astro's static site generation capabilities and ease of integration with other frameworks made it a breeze to get started. For styling, I chose Tailwind CSS, which offers utility-first classes that speed up the design process and make it easy to create responsive layouts. One of my early hurdles was configuring light and dark modes. I integrated shadcn UI components and set up a React-based mode toggle, troubleshooting some Tailwind configuration quirks along the way. Getting the background and text colors to play nicely in both modes took a bit of trial and error, but the end result was a theme that adapts seamlessly to user preference.

## Designing the Dashboard Layout

The vision for my dashboard was to create a bento-style grid layout, with each section represented as a card. Here's what I included:
- Active Dev Feed: Pulls live data from the GitHub API, showing recent commits, pull requests, and repository activity.
- Toolbox: Highlights the tools and software I'm currently using, from editors like PHP Storm to workflow enhancers like Raycast.
- IDE Setup: Details my development environment, including themes, keybindings, and extensions.
- Speaking Events & Timeline: Lists upcoming and past speaking engagements, with details like date, topic, and location.
- Goals & OKRs: Outlines my current objectives, such as growing my developer audience, publishing content, and launching new products.
- Notes: A catch-all for random thoughts, ideas, and technical notes I want to keep handy.
- Tech Stack Overview: Summarizes the languages (PHP, TypeScript, Go), frameworks (Laravel, Nuxt, Astro), and infrastructure (Cloudflare, Sevalla) I rely on.
- Clients, Projects, and Responsibilities: Offers a transparent look at who I'm working with and what I'm building.
- Content Pipeline: Tracks the status of content pieces, from draft to publication.
- Pomodoro Timer: A built-in timer to help manage focus and break sessions, complete with real-time browser tab updates.

Using Tailwind's grid and flex utilities, I was able to arrange these components in a way that's both visually appealing and easy to navigate. Each card is reusable, making it simple to add or rearrange sections as my needs change.

## Integrating Dynamic Content

A key feature of this dashboard is its ability to pull in live data. For the dev feed, I connected to the GitHub API, filtering for relevant events like pushes, pull requests, and issues. This not only keeps the dashboard up to date automatically, but also provides a real-time window into my open-source activity. For speaking events, I combined static data (for past talks) with an easy system to add new events as they're scheduled. The notes and goals sections are similarly flexible, allowing me to quickly jot down ideas or update objectives.

## Productivity Features: The Pomodoro Timer

One of my favorite additions is the Pomodoro timer. I built a simple React component that tracks focus and break intervals, updating the page and browser tab title in real time. This means I can keep an eye on my focus session even if I'm working in a different tab or window. The timer's state is managed with React's useEffect hook, and toggling between focus and break is as simple as clicking a button. It's a small feature, but it's made a noticeable difference in my workflow.

## Deployment with Sevalla and Cloudflare

With the site ready, it was time to deploy. I chose Sevalla for its speed and simplicity, along with Cloudflare for edge hosting and fast global delivery. The deployment process was straightforward: push updates to GitHub, and Sevalla takes care of building and deploying the site automatically. Static site hosting is free, and the first deploy took less than 30 seconds. I also experimented with Nux Hub for another project, but for this dashboard, Sevalla and Cloudflare were the ideal combo.

## Reflections and What's Next

Now that [now.juststeveking.com](now.juststeveking.com) is live, I'm already thinking about future enhancements. I plan to make the cards clickable for more detailed views, and perhaps add features like filtering or searching activity. The dashboard has become my digital command center—a place to track progress, share updates, and experiment with new tech.

For anyone considering a similar project, I can't recommend Astro enough. It's fast, flexible, and plays well with modern tools like Tailwind and React. Integrating APIs and productivity tools is straightforward, and deployment with platforms like Sevalla and Cloudflare makes it easy to keep your site up to date with minimal friction.

If you're looking to build a personal dashboard or "now" page, I hope my experience gives you a helpful roadmap. Stay tuned for future deep dives, where I'll tackle backend topics like CQRS in Laravel and more!
