@props(['user', 'isFollowing' => false])

@guest
    <a href="{{ route('login') }}" class="btn-outline text-xs">Follow</a>
@else
    @if(auth()->id() === $user->id)
        {{-- No self-follow --}}
    @elseif(! auth()->user()->hasVerifiedEmail())
        <a href="{{ route('verification.notice') }}" class="btn-outline text-xs">Follow</a>
    @elseif($isFollowing)
        <form method="POST" action="{{ route('users.unfollow', $user) }}" class="inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-outline text-xs">Following</button>
        </form>
    @else
        <form method="POST" action="{{ route('users.follow', $user) }}" class="inline">
            @csrf
            <button type="submit" class="btn-primary text-xs">Follow</button>
        </form>
    @endif
@endguest
