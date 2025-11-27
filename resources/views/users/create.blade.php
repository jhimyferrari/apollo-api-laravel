<div>
    <h1>Criar Usuário</h1>
    <a href={{  route('index') }}>Voltar para o menu</a>
    <form action={{ route('users.store') }} method="post">
        @csrf
        <div>
            <label for="">Nome</label>
            <input type="text" name="name">
        </div>
        <div>
            <label for="">Email</label>
            <input type="email" name="email">
        </div>
        <div>
            <label for="">Senha</label>
            <input type="password" name="password">
        </div>
        <button type="submit">Cadastrar</button>

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
