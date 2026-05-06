<?php
$page_title = isset($page_title) ? $page_title : '';
$show_search = isset($show_search) ? $show_search : false;
?>
<header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-8 shadow-sm shrink-0">
    <?php if ($show_search): ?>
        <div class="flex items-center gap-4 w-1/2">
            <i data-lucide="search" class="w-5 h-5 text-slate-400"></i>
            <input type="text" placeholder="Buscar en el sistema..." class="w-full bg-transparent outline-none text-slate-600 placeholder-slate-400 font-medium">
        </div>
    <?php else: ?>
        <h1 class="text-xl font-black text-slate-800 uppercase tracking-tight"><?php echo htmlspecialchars($page_title); ?></h1>
    <?php endif; ?>
    
    <div class="flex items-center gap-6">
        <button class="relative text-slate-400 hover:text-brand transition-colors">
            <i data-lucide="bell" class="w-6 h-6"></i>
            <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full border-2 border-white"></span>
        </button>
        <div class="flex items-center gap-3 border-l pl-6">
            <img src="https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&w=100&q=80" alt="Perfil" class="w-10 h-10 rounded-full object-cover border-2 border-teal-500">
            <div class="hidden md:block">
                <p class="text-sm font-bold text-slate-800"><?php echo isset($_SESSION['usuario_nombre']) ? htmlspecialchars($_SESSION['usuario_nombre']) : 'Usuario'; ?></p>
                <p class="text-xs text-brand font-medium"><?php echo isset($_SESSION['usuario_rol']) ? htmlspecialchars($_SESSION['usuario_rol']) : 'Rol'; ?></p>
            </div>
        </div>
    </div>
</header>
