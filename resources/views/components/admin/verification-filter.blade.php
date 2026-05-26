<select name="verified" class="input-field max-w-xs">
    <option value="">All verification</option>
    <option value="verified" @selected(request('verified') === 'verified')>Verified</option>
    <option value="unverified" @selected(request('verified') === 'unverified')>Not verified</option>
</select>
