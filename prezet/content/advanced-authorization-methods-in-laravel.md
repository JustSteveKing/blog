---
title: Advanced Authorization methods in Laravel
excerpt: Unravel Laravels authorization built-in Gates, Policies & advanced ReBAC, ABAC, PBAC methods for secure, scalable app access control.
date: 2025-02-13
image: "/images/articles/advanced-authorization.png"
minRead: 7
category: authentication
sponsor:
  name: Permit
  url: https://juststeveking.link/permit
  logo: https://github.com/permitio.png
author: steve
tags: ["laravel", "authorization", "security", "access-control"]
---

Authorization, often confused with authentication, is a crucial aspect of web application security. In this article, we will explore Laravel’s built-in authorization features and dive into alternative models such as ReBAC, ABAC, and PBAC, helping you choose the right approach for your application. In Laravel we use an assortment of different approaches, which mostly revolves around RBAC, or Role Based Access Control for those not into acronyms. Is that all? Surely there must be more options to protect the valuable data our apps collect. Let's talk about some Laravel Authorization best practices, and see how we can implement them into our projects. 

Out of the box Laravel provides robust mechanisms, in the form of Gates and Policies, but sometimes we need a little more granular control that this approach. So, we are going to investigate three alternatives to RBAC that you can implement in Laravel today, and discuss when and why we might use them. We will look at Role-based vs Attribute-based access control in Laravel, finishing off with Policy-based access control and trying to decide which approach is going to be best for which use-case.

## Understanding Laravel's Default Authorization

Before diving into more advanced models of Authorization, we need to have a handle on the basics. By default Laravel will allow you to define `Gates` and `Policies`, using a mixture of these you can achieve an RBAC based system that works relatively well. Let's look at an example Gate implementation to see how it is structured.

```php
// In your Service Provider
Gate::define(
    'delete-tasks',
    fn (User $user) $user->hasVerifiedEmail(),
);

// In your application
if ( ! Gate::allows('delete-tasks')) {
	throw new UnauthorizedException(
		message: 'You do not have permission to delete tasks',
		code: Response::HTTP_UNAUTHORIZED,
	);
}

$task->delete();
```

Relatively straight forward. We set up a new Gate definition, give it a callable as a constraint and that's all. Once that has been done we can leverage the Gate Facade to see if the `delete-tasks` gate will allow the currently authenticated user to move forwards. These are particularly helpful when you have a somewhat simple application that has a small set of predefined rules that you can check against.

Alongside this however is Laravel's Policy implementation, which will allow you to check direct ownership on Eloquent models. It will also allow you to do indirect ownership, but it is a little trickier and can spiral out of control with n+1 queries. Let's take a look at a policy, but we will focus on the one or two methods.

```php
final class TaskPolicy
{
	public function view(User $user, Task $task): bool
	{
		if ( ! $user->hasVerifiedEmail()) {
			return false;
		}

		return $user->id === $task->user_id;
	}

	public function create(User $user): bool
	{
		return $user->hasVerifiedEmail();
	}
}
```

Here we have two methods, `view` and `create` which keeps the logical flow of who can do what to what in your Laravel application.  We can take this a step further by implementing what's called a Policy "filter", which is a method that will be ran before any other policy check and is useful for things like administrative users.

```php
final class TaskPolicy
{
	public function before(User $user): bool|null
	{
		if ($user->isAdmin()) {
			return true;
		}

		return null;
	}
}
```

This approach works well, but it requires all logic to be embedded in your policies as code. This means authorization becomes a developer-centric task, which can become challenging as applications scale. As you can imagine, the authorization logic can get more and more complex as your application starts to do more. Imagine what the Gate and Policy would look like for an application as complex as Jira ...

## What is ReBAC?

ReBAC, which stands for Relationship Based Access Control is a method used to check the relationships between entities for authorization. This, again, isn't too difficult to implement directly inside of a Laravel application. There are times when this style authorization mechanism is particularly useful, such as collaboration tools or social applications. In essence we want to see if the authenticated entity is an owner of another entity, or a collaborator of another entity, or any other relation that you may wish to create. We can actually achieve this in a Laravel Policy.

```php
final class ProjectPolicy
{
	public function addTask(User $user, Project $project): bool
	{
		if ( ! $user->hasVerifiedEmail()) {
			return false;
		}

		return $user->projects->contains($project);
	}
}
```

Again we have a sanity check to make sure the user has verified their email address, then we want to see if the user belongs to this project through the users Projects relationship.  We would then do the check in our code exactly the same way as we usually would with a Policy. The biggest drawback here is that is really does rely on you having good relationship management, and if you aren't going to lean on the ORM you need to make sure you use effective join queries.

You can use something like ReBAC alongside something like RBAC, as they do play quite nicely together - but it will massively depend on the application you are building. A lot of the applications I have built in my career could benefit from this approach, but there are certain scenarios where this just isn't enough. As the data you are storing and providing access to gets more sensitive or personal, you are going to want to start looking for alternative methods of Authorization to help you out.

## How about ABAC?

Ok, I promise I am not making up acronyms now. ABAC stands for Attribute Based Access Control, which extends access control to a new level. It considers various attributes that an entity may have, which provides a super flexible and fine-grained approach to Authorization. It definitely sounds a lot more complex than it actually is, so let's walk through an example.

Imagine that we have built a document storage application for our company, and documents belong to departments. Here we can implement something similar to what we have already done right, it's just checking direct ownership. However, we want to make sure that the user logged in is part of the department the document belongs to. So we are seeing a little bit of RBAC and ReBAC here. Now let's add another constraint, documents can only be accessed during business hours. There are many reasons for this sort of behaviour, but let's roll with it a second. We can use our Gate and Policy to implement this, with relative ease.

```php
final class DocumentPolicy
{
	public function view(User $user, Document $document): bool
	{
		return $user->department === $document->department 
			&& now()->hour >= 9 
			&& now()->hour <= 17;
	}
}
```

Yes, maybe not the most _concrete_ logic, but this is an example right? We want to make sure that the users department is the same as the documents department, but we also want to make sure that the hour is in-between 9 and 5, aka typical business hours. Then we can add our Gate.

```php
// In a Service Provider
Gate::define('view-document', function (User $user, Document $document) {
    return $user->department === $document->department && $user->is_active;
});
```

We can combine the Gate and Policy to achieve ABAC in Laravel, but a word of caution. While ABAC is perfect for complex business rules requiring multiple attributes - it can become extremely difficult to scale if too many attributes are involved. This is where we want to look for alternative options, again.

## Why not PBAC?

I promise I am not making these up, in-fact I googled them when I first heard the term PBAC because I thought someone was making it up. So, if we follow it along we know that it is some Based Access Control. But, what is the P? Policy Based Access Control focuses on high-level Policies that dictate how resources are accessed based on contextual conditions , combining aspects of both ReBAC and ABAC together. It sounds like we fell down a rabbit hole of combining all of the Authorization mechanisms together right? But, trust me, it definitely has its place!

Imagine for a second that we are working for a hedge fund, and alongside the sharp suits and schmoozing clients we need to manage our clients budgets. We have some software that handles this, but only senior managers are able to approve budget changes for certain high-value clients/funds. Still with me? Good, let's keep going.

We could implement something like this inside of a Laravel Policy, I mean it is in the name. Let't take a quick peek and see what this may look like.

```php
final class ClientPolicy
{
	public function approveBudget(User $user, Client $client)
	{
		if ($client->revenue >= 1_000_000) {
			return $user->role === 'senior-manager' 
				&& $client->budget > 100_000;	
		}

		// other logical checks
	}
}
```

This works relatively well, but we have a few drawbacks here. This logic can pile up pretty quickly, and it ends up with floating strings and numbers holding together our Authorization logic.

We "could" use something like a JSON or YAML file to define these policies to make our Policies cleaner, but we are just shifting the problem. This is where a solution such as Permit really shines, it abstracts all of this away from your application - giving you a centralised service that you can lean on, your non technical team can manage and maintain . Leaving you to just worry about direct logic and the integration code.

## Comparing the Options

Let's compare these options for a second, and see it all from a high level perspective.

| **Feature**    | **ReBAC**                       | **ABAC**                       | **PBAC**                   |
| -------------- | ------------------------------- | ------------------------------ | -------------------------- |
| Control Basis  | Relationships between entities. | Attributes of users/resources. | Defined policies and rules |
| Flexibility    | Moderate                        | High                           | Very High                  |
| Implementation | Relationships via Eloquent.     | Dynamic Attribute checks.      | Policy Definition          |
| Best For       | Social Apps, Collaborations.    | Complex business rules.        | Enterprise-level apps.     |

As you can see from this wonderful table, when looking for the right Authorization mechanism you have to balance ease of implementation against flexibility of control against what would work best for your use case. Not all Social Apps would want to use ReBAC for example.

## Choosing the right Authorization Model

When it comes to choosing the right Authorization model for your Laravel application, you need to consider the complexity of your application. For example, if relationships between entities is your primary logic then ReBAC makes the most sense. For more complex business applications, ABAC is going to allow you to get the control you need.

Performance is a big thing in all web applications, and Laravel is no different. If you don't have the right foreign key relationships on your database or your database doesn't support them, or you aren't the most familiar with joins in SQL then ReBAC is going to be pretty heavy on the performance aspect.

Finally, the big one. The one everyone always asks. Does it scale? Scalability is key for any application, and your Authorization model is no different. Having the ability to scale out your Authorization to cover more use cases, and meet new and changing demands of the business is key. If scalability is your main concern then ABAC or PBAC would be the right choice for you.

## Conclusion

To some it all up, Laravel has fantastic Authorization functionality but the problem comes in managing the logic around Authorization. You can leverage ReBAC, ABAC, or PBAC to create a more granular and flexible access controls. It is important to evaluate your specific requirements and choose the model that is going to align best with your performance, management, and scalability needs. 

