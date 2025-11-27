<div>
    <h1>Atualizar Usuário</h1>
    <a href={{  route('index') }}>Voltar para o menu</a>
    <form action={{ route('users.update',$user) }} method="post">
        @csrf
        @method('PUT')
        <div>
            <label for="">Nome</label>
            <input type="text" name="name" value={{ $user->name }}>
        </div>
        <div>
            <label for="">Email</label>
            <input type="email" name="email" value={{ $user->email}}>
        </div>
        
        <button type="submit">Atualizar</button>

        @if (session('success'))
            <div style="color: green;">
                {{ session('success') }}
            </div>
        
        @endif
        @if ($errors->any())
            <div style="color: red;">
                <ul>
                    @foreach ($errors->all() as $erro )
                        <li>{{ $erro }}</li>
                    @endforeach
                </ul>
            </div>
        
        @endif

    </form>
</div>
