package com.example.linkaloo

import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.util.Log
import androidx.appcompat.app.AppCompatActivity

class ShareReceiverActivity : AppCompatActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        when (intent?.action) {
            Intent.ACTION_SEND -> {
                if ("text/plain" == intent.type) {
                    val sharedText = intent.getStringExtra(Intent.EXTRA_TEXT)
                    sharedText?.let { handleLink(it) }
                }
            }
            Intent.ACTION_VIEW -> {
                val data: Uri? = intent.data
                data?.toString()?.let { handleLink(it) }
            }
            Intent.ACTION_SEND_MULTIPLE -> {
                // Manejo de múltiples elementos si lo necesitas
            }
        }

        // Redirige a tu Main si procede o muestra una UI ligera
        finish()
    }

    private fun handleLink(link: String) {
        Log.d("ShareReceiver", "Received link: $link")
        // TODO: procesar el enlace (link)
    }
}
