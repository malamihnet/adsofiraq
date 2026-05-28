<x-mail::message>
# {{ $campaign->title }}

@switch($event)
@case('approved')
Your campaign is now live on Ads of Iraq.
@break
@case('needs_changes')
Our editorial team has requested changes before we can publish this work.
@break
@case('rejected')
We were unable to approve this submission at this time.
@break
@case('featured')
Congratulations — your campaign has been featured by our editors.
@break
@default
There is an update on your campaign submission.
@endswitch

@if($notes)
**Editorial notes**

{{ $notes }}
@endif

<x-mail::button :url="$url">
View campaign
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
