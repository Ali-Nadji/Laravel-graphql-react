@extends('layout')

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            Jobmaker
            <small>Tableau de bord</small>
        </h1>
    </section>


    <section class="content">


        <div class="row">
            <div class="col-md-6">

                <input type="checkbox" name="my-checkbox" checked>

            </div>
        </div>


    </section>


@endsection

@section('inline')
    <script>

        $(function() {
            $("[name='my-checkbox']").bootstrapSwitch( {
                'onText' : 'Flux',
                'offText' : 'Solde',
                'onColor' : 'primary',
                'offColor' : 'success',
                'labelWidth' : '200px',
                'labelText' : "Au total il y aura une diminution de 150 emplois de cadres dans les sièges régionaux"
            });
//            new SimpleMDE({ element: document.getElementById("test") });
        });
    </script>

@endsection
