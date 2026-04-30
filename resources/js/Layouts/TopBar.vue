<template>
    <nav class="navbar navbar-expand-lg bg-body-tertiary sticky-top" style="z-index: 1">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarExample"
                aria-controls="navbarExample" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand" href="#"><img src="/images/bootstrap-logo.svg" width="36" /></a>
            <div class="collapse navbar-collapse" id="navbarExample">
                <ul class="navbar-nav me-auto mb-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="#">Team</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                            aria-expanded="false">Projects</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Action</a></li>
                            <li><a class="dropdown-item" href="#">Another action</a></li>
                            <li>
                                <hr class="dropdown-divider" />
                            </li>
                            <li><a class="dropdown-item" href="#">Something else here</a></li>
                        </ul>
                    </li>
                </ul>
                <div class="d-flex align-items-center flex-column flex-lg-row">
                    <form class="me-2 mb-2 mb-lg-0">
                        <input type="text" class="form-control form-control-sm" placeholder="Search" />
                    </form>
                    <a class="btn btn-primary" href="">Sign up</a>
                </div>
            </div>
        </div>
    </nav>
</template>