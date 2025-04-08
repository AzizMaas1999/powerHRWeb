<?php

namespace App\Enum;

enum Poste: string
{
    case FACTURATION = 'Facturation';
    case OUVRIER = 'Ouvrier';
    case ADMIN = 'Admin';
    case DIRECTEUR = 'Directeur';
    case CHARGES = 'Charges';
}